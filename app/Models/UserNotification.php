<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content_path', 'image_paths'];

    // Chuyển đổi cột image_paths từ dạng JSON thành array khi thao tác
    protected $casts = [
        'image_paths' => 'array',
    ];
}
