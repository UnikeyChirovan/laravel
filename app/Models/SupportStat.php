<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_conversation_id',
        'manager_id',
        'response_time',
        'resolution_time',
        'message_count',
        'was_transferred',
        'transferred_from',
        'claimed_at',
        'resolved_at',
    ];

    protected $casts = [
        'was_transferred' => 'boolean',
        'claimed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function transferredFromManager()
    {
        return $this->belongsTo(User::class, 'transferred_from');
    }

    // Helper methods
    public function calculateResponseTime($firstResponseAt)
    {
        if ($this->claimed_at && $firstResponseAt) {
            $this->update([
                'response_time' => $firstResponseAt->diffInSeconds($this->claimed_at),
            ]);
        }
    }

    public function incrementMessageCount()
    {
        $this->increment('message_count');
    }
}