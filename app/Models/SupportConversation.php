<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'assigned_to',
        'assigned_at',
        'resolved_at',
        'closed_at',
        'last_message_at',
        'rating',
        'rating_comment',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedManager()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'support_conversation_id');
    }

    public function stats()
    {
        return $this->hasMany(SupportStat::class, 'support_conversation_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeAssignedTo($query, $managerId)
    {
        return $query->where('assigned_to', $managerId);
    }

    // Helper methods
    public function claim($managerId)
    {
        $this->update([
            'status' => 'active',
            'assigned_to' => $managerId,
            'assigned_at' => now(),
        ]);

        // Tạo stat record
        SupportStat::create([
            'support_conversation_id' => $this->id,
            'manager_id' => $managerId,
            'claimed_at' => now(),
        ]);
    }

    public function transfer($newManagerId)
    {
        $oldManagerId = $this->assigned_to;
        
        // Cập nhật stat cũ
        $oldStat = $this->stats()->where('manager_id', $oldManagerId)->latest()->first();
        if ($oldStat) {
            $oldStat->update([
                'resolved_at' => now(),
                'resolution_time' => now()->diffInSeconds($oldStat->claimed_at),
            ]);
        }

        // Tạo stat mới
        SupportStat::create([
            'support_conversation_id' => $this->id,
            'manager_id' => $newManagerId,
            'was_transferred' => true,
            'transferred_from' => $oldManagerId,
            'claimed_at' => now(),
        ]);

        // Cập nhật conversation
        $this->update([
            'assigned_to' => $newManagerId,
            'assigned_at' => now(),
        ]);
    }

    public function resolve()
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        // Cập nhật stat
        $stat = $this->stats()->where('manager_id', $this->assigned_to)->latest()->first();
        if ($stat) {
            $stat->update([
                'resolved_at' => now(),
                'resolution_time' => now()->diffInSeconds($stat->claimed_at),
            ]);
        }
    }

    public function getUnreadCountForUser()
    {
        return $this->messages()
            ->where('sender_type', 'support')
            ->where('is_read', false)
            ->count();
    }

    public function getUnreadCountForSupport()
    {
        return $this->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->count();
    }
}