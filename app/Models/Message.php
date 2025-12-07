<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $appends = ['timestamp'];

    // Relationship: Conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // Relationship: Sender
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relationship: Receiver
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Accessor for timestamp (for frontend)
    public function getTimestampAttribute()
    {
        return $this->created_at->toISOString();
    }

    // Mark message as read
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}