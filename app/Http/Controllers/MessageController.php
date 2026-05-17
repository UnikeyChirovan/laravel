<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // Send a message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $senderId = Auth::id();
        $receiverId = $request->receiver_id;

        // Check if sender is blocked by receiver or vice versa
        $isBlocked = \DB::table('blocks')
            ->where(function($query) use ($senderId, $receiverId) {
                $query->where('blocker_id', $senderId)
                      ->where('blocked_id', $receiverId);
            })
            ->orWhere(function($query) use ($senderId, $receiverId) {
                $query->where('blocker_id', $receiverId)
                      ->where('blocked_id', $senderId);
            })
            ->exists();

        if ($isBlocked) {
            return response()->json(['message' => 'Không thể gởi tin nhắn cho người dùng này.'], 403);
        }

        $receiverSettings = \App\Models\UserMessagingSetting::getOrCreateForUser($receiverId);

        // Nếu KHÔNG cho phép nhắn tin từ người lạ + KHÔNG phải người theo dõi
        if (!$receiverSettings->allow_messages) {
            $isFollowing = \DB::table('follows')
                ->where('follower_id', $senderId)
                ->where('following_id', $receiverId)
                ->exists();

            if (!$isFollowing) {
                return response()->json(['message' => 'Người dùng chỉ nhận tin nhắn từ người theo dõi.'], 403);
            }
        }

        // Find or create conversation
        $conversation = Conversation::findOrCreateConversation($senderId, $receiverId);

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $request->message,
        ]);

        // Update conversation last_message_at
        $conversation->update(['last_message_at' => now()]);

        // Load sender relationship
        $message->load('sender');

        // Broadcast message via Reverb
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    // Get conversation messages
    public function getMessages($conversationId)
    {
        $userId = Auth::id();
        
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is part of this conversation
        if ($conversation->user_one_id != $userId && $conversation->user_two_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get last 20 messages
        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    // Mark messages as read
    public function markAsRead($conversationId)
    {
        $userId = Auth::id();

        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is part of this conversation
        if ($conversation->user_one_id != $userId && $conversation->user_two_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark all unread messages from the other user as read
        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    // Get unread count
    public function getUnreadCount()
    {
        $userId = Auth::id();

        $unreadCount = Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    // Delete a single message
    public function deleteMessage($messageId)
    {
        $userId = Auth::id();
        
        $message = Message::findOrFail($messageId);
        
        // Only sender can delete their own message
        if ($message->sender_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $message->delete();
        
        return response()->json(['success' => true], 200);
    }

    // Delete all messages in a conversation
    public function deleteAllMessages($conversationId)
    {
        $userId = Auth::id();
        
        $conversation = Conversation::findOrFail($conversationId);
        
        // Check if user is part of this conversation
        if ($conversation->user_one_id != $userId && $conversation->user_two_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Delete all messages
        Message::where('conversation_id', $conversationId)->delete();
        
        return response()->json(['success' => true], 200);
    }
}