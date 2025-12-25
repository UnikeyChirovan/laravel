<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // Relationship: User One
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    // Relationship: User Two
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    // Relationship: Messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Get the other user in the conversation
    public function getOtherUser($userId)
    {
        if ($this->user_one_id == $userId) {
            return $this->userTwo;
        }
        return $this->userOne;
    }

    // Get last message
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    // Get unread count for a user
    public function unreadCount($userId)
    {
        return $this->messages()
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    // Static method to find or create conversation
    public static function findOrCreateConversation($userOneId, $userTwoId)
    {
        // Ensure user_one_id is always smaller to maintain consistency
        $userIds = [$userOneId, $userTwoId];
        sort($userIds);

        return self::firstOrCreate([
            'user_one_id' => $userIds[0],
            'user_two_id' => $userIds[1],
        ]);
    }
}