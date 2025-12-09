<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportStat;
use App\Events\SupportMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupportChatController extends Controller
{
    // ============================================
    // USER ACTIONS
    // ============================================

    public function getOrCreateConversation()
    {
        $user = Auth::guard('api')->user();
        
        $conversation = SupportConversation::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'resolved'])
            ->with(['messages' => function($query) {
                $query->orderBy('created_at', 'asc');
            }, 'user', 'assignedManager'])
            ->first();

        if (!$conversation) {
            $conversation = SupportConversation::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'last_message_at' => now(),
            ]);
            
            $conversation->load(['messages', 'user', 'assignedManager']);
        }

        return response()->json([
            'conversation' => $this->formatConversationForUser($conversation),
        ], 200);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:support_conversations,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = Auth::guard('api')->user();
        $conversation = SupportConversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = SupportMessage::create([
            'support_conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'message' => $request->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($conversation->status === 'active' && $conversation->assigned_to) {
            $stat = $conversation->stats()->where('manager_id', $conversation->assigned_to)->latest()->first();
            if ($stat && !$stat->response_time) {
                $stat->calculateResponseTime($message->created_at);
            }
        }

        broadcast(new SupportMessageSent($message))->toOthers();

        return response()->json(['message' => $this->formatMessage($message)], 201);
    }

    public function markAsRead(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        $conversation = SupportConversation::findOrFail($conversationId);

        if ($conversation->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->messages()->where('sender_type', 'support')->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Marked as read'], 200);
    }

    public function getUnreadCount()
    {
        $user = Auth::guard('api')->user();
        $conversation = SupportConversation::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'resolved'])->first();

        $unreadCount = $conversation ? $conversation->getUnreadCountForUser() : 0;

        return response()->json(['unread_count' => $unreadCount], 200);
    }

    public function rateConversation(Request $request, $conversationId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::guard('api')->user();
        $conversation = SupportConversation::findOrFail($conversationId);

        if ($conversation->user_id !== $user->id || $conversation->status !== 'resolved') {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $conversation->update([
            'rating' => $request->rating,
            'rating_comment' => $request->comment,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json(['message' => 'Rating submitted'], 200);
    }

    // ============================================
    // MANAGER/ADMIN ACTIONS
    // ============================================

    public function getConversations(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $type = $request->query('type', 'pending');
        $query = SupportConversation::with(['user', 'assignedManager', 'messages' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(1);
        }]);

        if ($type === 'pending') {
            $query->where('status', 'pending');
        } elseif ($type === 'my_active') {
            $query->where('status', 'active')->where('assigned_to', $user->id);
        } elseif ($type === 'all_active') {
            if ($user->department_id !== 1) {
                return response()->json(['message' => 'Admin only'], 403);
            }
            $query->where('status', 'active');
        }

        $conversations = $query->orderBy('last_message_at', 'desc')->get();

        return response()->json([
            'conversations' => $conversations->map(fn($c) => $this->formatConversationForManager($c)),
        ], 200);
    }

    public function claimConversation(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation = SupportConversation::findOrFail($conversationId);
        if ($conversation->status !== 'pending') {
            return response()->json(['message' => 'Not pending'], 400);
        }

        $conversation->claim($user->id);

        return response()->json([
            'message' => 'Claimed',
            'conversation' => $this->formatConversationForManager($conversation->fresh(['user', 'assignedManager'])),
        ], 200);
    }

    public function sendManagerMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:support_conversations,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation = SupportConversation::findOrFail($request->conversation_id);
        if ($conversation->assigned_to !== $user->id && $user->department_id !== 1) {
            return response()->json(['message' => 'Not assigned'], 403);
        }

        $message = SupportMessage::create([
            'support_conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'support',
            'message' => $request->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $stat = $conversation->stats()->where('manager_id', $conversation->assigned_to)->latest()->first();
        if ($stat) {
            $stat->incrementMessageCount();
            if (!$stat->response_time) {
                $stat->calculateResponseTime($message->created_at);
            }
        }

        broadcast(new SupportMessageSent($message))->toOthers();

        return response()->json(['message' => $this->formatMessage($message)], 201);
    }

    public function markAsReadByManager(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation = SupportConversation::findOrFail($conversationId);
        $conversation->messages()->where('sender_type', 'user')->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Marked as read'], 200);
    }

    public function transferConversation(Request $request, $conversationId)
    {
        $request->validate(['new_manager_id' => 'required|exists:users,id']);

        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation = SupportConversation::findOrFail($conversationId);
        $newManager = User::findOrFail($request->new_manager_id);

        if (!in_array($newManager->department_id, [1, 3])) {
            return response()->json(['message' => 'Invalid manager'], 400);
        }

        if ($conversation->assigned_to !== $user->id && $user->department_id !== 1) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $conversation->transfer($request->new_manager_id);

        return response()->json(['message' => 'Transferred'], 200);
    }

    public function resolveConversation(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation = SupportConversation::findOrFail($conversationId);
        if ($conversation->assigned_to !== $user->id && $user->department_id !== 1) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $conversation->resolve();

        return response()->json(['message' => 'Resolved'], 200);
    }

    // ============================================
    // STATISTICS (ADMIN)
    // ============================================

    public function getStatistics(Request $request)
    {
        $user = Auth::guard('api')->user();
        if ($user->department_id !== 1) {
            return response()->json(['message' => 'Admin only'], 403);
        }

        $period = $request->query('period', 'today');
        $startDate = $this->getStartDate($period);

        $totalConversations = SupportConversation::where('created_at', '>=', $startDate)->count();
        $resolvedConversations = SupportConversation::where('status', 'resolved')->where('resolved_at', '>=', $startDate)->count();
        $pendingConversations = SupportConversation::where('status', 'pending')->count();
        $activeConversations = SupportConversation::where('status', 'active')->count();

        $avgResponseTime = SupportStat::where('created_at', '>=', $startDate)->whereNotNull('response_time')->avg('response_time');
        $avgResolutionTime = SupportStat::where('created_at', '>=', $startDate)->whereNotNull('resolution_time')->avg('resolution_time');
        $avgRating = SupportConversation::where('resolved_at', '>=', $startDate)->whereNotNull('rating')->avg('rating');

        $topManagers = $this->getTopManagers($startDate);

        return response()->json([
            'period' => $period,
            'total_conversations' => $totalConversations,
            'resolved_conversations' => $resolvedConversations,
            'pending_conversations' => $pendingConversations,
            'active_conversations' => $activeConversations,
            'avg_response_time' => round($avgResponseTime ?? 0),
            'avg_resolution_time' => round($avgResolutionTime ?? 0),
            'avg_rating' => round($avgRating ?? 0, 2),
            'top_managers' => $topManagers,
        ], 200);
    }

    public function getManagerStatistics(Request $request, $managerId)
    {
        $user = Auth::guard('api')->user();
        if ($user->department_id !== 1 && $user->id != $managerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $period = $request->query('period', 'today');
        $startDate = $this->getStartDate($period);
        $manager = User::findOrFail($managerId);

        $handledConversations = SupportStat::where('manager_id', $managerId)->where('created_at', '>=', $startDate)->count();
        $resolvedConversations = SupportStat::where('manager_id', $managerId)->whereNotNull('resolved_at')->where('resolved_at', '>=', $startDate)->count();
        $avgResponseTime = SupportStat::where('manager_id', $managerId)->where('created_at', '>=', $startDate)->whereNotNull('response_time')->avg('response_time');
        $avgResolutionTime = SupportStat::where('manager_id', $managerId)->where('created_at', '>=', $startDate)->whereNotNull('resolution_time')->avg('resolution_time');
        $totalMessages = SupportStat::where('manager_id', $managerId)->where('created_at', '>=', $startDate)->sum('message_count');
        $avgRating = SupportConversation::where('assigned_to', $managerId)->where('status', 'closed')->where('closed_at', '>=', $startDate)->whereNotNull('rating')->avg('rating');
        $transferredCount = SupportStat::where('manager_id', $managerId)->where('was_transferred', true)->where('created_at', '>=', $startDate)->count();

        return response()->json([
            'manager' => ['id' => $manager->id, 'name' => $manager->name, 'avatar' => $manager->avatar],
            'period' => $period,
            'handled_conversations' => $handledConversations,
            'resolved_conversations' => $resolvedConversations,
            'avg_response_time' => round($avgResponseTime ?? 0),
            'avg_resolution_time' => round($avgResolutionTime ?? 0),
            'total_messages' => $totalMessages,
            'avg_rating' => round($avgRating ?? 0, 2),
            'transferred_count' => $transferredCount,
        ], 200);
    }

    public function checkSupportOnline()
    {
        $onlineSupport = User::whereIn('department_id', [1, 3])
            ->where(function($query) {
                $query->whereNotNull('last_seen_at')->where('last_seen_at', '>=', now()->subMinutes(5));
            })->exists();

        return response()->json(['online' => $onlineSupport], 200);
    }

    public function getManagers()
    {
        $user = Auth::guard('api')->user();
        if (!in_array($user->department_id, [1, 3])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $managers = User::whereIn('department_id', [1, 3])->select('id', 'name', 'avatar', 'department_id')->get();

        return response()->json(['managers' => $managers], 200);
    }

    // ============================================
    // HELPERS
    // ============================================

    private function formatConversationForUser($conversation)
    {
        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'created_at' => $conversation->created_at->toISOString(),
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'unread_count' => $conversation->getUnreadCountForUser(),
            'messages' => $conversation->messages->map(fn($m) => $this->formatMessage($m)),
            'assigned_manager' => $conversation->assignedManager ? ['id' => $conversation->assignedManager->id, 'name' => 'Support Team'] : null,
        ];
    }

    private function formatConversationForManager($conversation)
    {
        $lastMessage = $conversation->messages->first();
        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'user' => ['id' => $conversation->user->id, 'name' => $conversation->user->name, 'avatar' => $conversation->user->avatar],
            'assigned_to' => $conversation->assigned_to,
            'assigned_manager' => $conversation->assignedManager ? ['id' => $conversation->assignedManager->id, 'name' => $conversation->assignedManager->name] : null,
            'last_message' => $lastMessage ? ['message' => $lastMessage->message, 'timestamp' => $lastMessage->created_at->toISOString()] : null,
            'unread_count' => $conversation->getUnreadCountForSupport(),
            'created_at' => $conversation->created_at->toISOString(),
            'last_message_at' => $conversation->last_message_at?->toISOString(),
        ];
    }

    private function formatMessage($message)
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'message' => $message->message,
            'is_read' => $message->is_read,
            'timestamp' => $message->created_at->toISOString(),
        ];
    }

    private function getStartDate($period)
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'all' => now()->subYears(10),
            default => now()->startOfDay(),
        };
    }

    private function getTopManagers($startDate, $limit = 10)
    {
        $topManagers = SupportStat::select('manager_id', DB::raw('COUNT(*) as total_conversations'), DB::raw('AVG(response_time) as avg_response_time'), DB::raw('AVG(resolution_time) as avg_resolution_time'))
            ->where('created_at', '>=', $startDate)->groupBy('manager_id')->orderBy('total_conversations', 'desc')->limit($limit)->get();

        return $topManagers->map(function($stat) {
            $manager = User::find($stat->manager_id);
            $avgRating = SupportConversation::where('assigned_to', $stat->manager_id)->where('status', 'closed')->whereNotNull('rating')->avg('rating');

            return [
                'manager' => ['id' => $manager->id, 'name' => $manager->name, 'avatar' => $manager->avatar],
                'total_conversations' => $stat->total_conversations,
                'avg_response_time' => round($stat->avg_response_time ?? 0),
                'avg_resolution_time' => round($stat->avg_resolution_time ?? 0),
                'avg_rating' => round($avgRating ?? 0, 2),
            ];
        });
    }
}