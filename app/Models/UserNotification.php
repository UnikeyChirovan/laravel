<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content_path', 'image_paths', 'page'];

    protected $casts = [
        'image_paths' => 'array',
    ];
}
