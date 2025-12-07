<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnlineStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $online;

    public function __construct($userId, $online)
    {
        $this->userId = $userId;
        $this->online = $online;
    }

    public function broadcastOn(): array
    {
        // Broadcast to public channel that all users can listen to
        return [
            new Channel('user.status'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'online' => $this->online,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.status';
    }
}