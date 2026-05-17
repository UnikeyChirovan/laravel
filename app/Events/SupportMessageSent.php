<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(SupportMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;
        
        // Broadcast to both user and assigned manager
        $channels = [
            new PrivateChannel('support.user.' . $conversation->user_id),
        ];

        // Nếu có manager được assign, broadcast cho manager đó
        if ($conversation->assigned_to) {
            $channels[] = new PrivateChannel('support.manager.' . $conversation->assigned_to);
        }

        // Luôn broadcast cho admin (department_id = 1)
        $channels[] = new PrivateChannel('support.admin');

        return $channels;
    }

    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation;
        
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->support_conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
            'message' => $this->message->message,
            'is_read' => $this->message->is_read,
            'timestamp' => $this->message->created_at->toISOString(),
            'conversation' => [
                'id' => $conversation->id,
                'user_id' => $conversation->user_id,
                'status' => $conversation->status,
                'assigned_to' => $conversation->assigned_to,
                'user' => [
                    'id' => $conversation->user->id,
                    'name' => $conversation->user->name,
                    'avatar' => $conversation->user->avatar,
                ],
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.message.sent';
    }
}