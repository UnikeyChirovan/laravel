<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        // Broadcast to private channel of the receiver
        return [
            new PrivateChannel('chat.' . $this->message->receiver_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message' => $this->message->message,
            'is_read' => $this->message->is_read,
            'timestamp' => $this->message->created_at->toISOString(),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}