<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'title',
        'content_path',
        'image_paths',
        'page',
        'type'
    ];

    protected $casts = [
        'image_paths' => 'array',
    ];

    public function vote()
    {
        return $this->hasOne(NotificationVote::class, 'notification_id');
    }
}
