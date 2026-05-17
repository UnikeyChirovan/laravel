<?php
// app/Events/SupportConversationResolved.php

namespace App\Events;

use App\Models\SupportConversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportConversationResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;
    public $status;

    public function __construct(SupportConversation $conversation)
    {
        $this->conversationId = $conversation->id;
        $this->userId = $conversation->user_id;
        $this->status = 'resolved';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('support.user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'support.conversation.resolved';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversationId,
            'status' => $this->status,
        ];
    }
}