<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    // Get all conversations for authenticated user
    public function index()
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne:id,name,avatar', 'userTwo:id,name,avatar', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($userId) {
                $otherUser = $conversation->getOtherUser($userId);
                $unreadCount = $conversation->unreadCount($userId);
                $lastMessage = $conversation->lastMessage;

                return [
                    'id' => $conversation->id,
                    'user_id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'last_message' => $lastMessage ? $lastMessage->message : null,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at->toISOString() : null,
                    'unread' => $unreadCount,
                    'online' => false, // Will be updated via WebSocket
                ];
            });

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    // Get or create conversation with a user
    public function getOrCreate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = Auth::id();
        $otherUserId = $request->user_id;

        if ($userId == $otherUserId) {
            return response()->json(['message' => 'Không thể tạo cuộc trò chuyện với chính mình.'], 400);
        }

        // Check if blocked
        $isBlocked = \DB::table('blocks')
            ->where(function($query) use ($userId, $otherUserId) {
                $query->where('blocker_id', $userId)
                      ->where('blocked_id', $otherUserId);
            })
            ->orWhere(function($query) use ($userId, $otherUserId) {
                $query->where('blocker_id', $otherUserId)
                      ->where('blocked_id', $userId);
            })
            ->exists();

        if ($isBlocked) {
            return response()->json(['message' => 'Không thể tạo cuộc trò chuyện với người dùng này.'], 403);
        }

        $conversation = Conversation::findOrCreateConversation($userId, $otherUserId);
        $otherUser = $conversation->getOtherUser($userId);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'user_id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'online' => false,
            ],
        ]);
    }

    // Delete conversation (soft delete - just remove from user's view)
    public function destroy($conversationId)
    {
        $userId = Auth::id();
        
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is part of this conversation
        if ($conversation->user_one_id != $userId && $conversation->user_two_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete all messages in this conversation
        $conversation->messages()->delete();
        
        // Delete conversation
        $conversation->delete();

        return response()->json(['success' => true], 204);
    }
}