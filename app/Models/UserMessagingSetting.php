<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMessagingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'allow_messages',
        'notifications',
        'sound',
        'show_online_status',
    ];

    protected $casts = [
        'allow_messages' => 'boolean',
        'notifications' => 'boolean',
        'sound' => 'boolean',
        'show_online_status' => 'boolean',
    ];

    // Relationship: User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get or create default settings for user
    public static function getOrCreateForUser($userId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'allow_messages' => true,
                'notifications' => true,
                'sound' => true,
                'show_online_status' => true,
            ]
        );
    }
}