<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoManager extends Model
{
    protected $fillable = ['video_name', 'video_path', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean', // Ép kiểu thành boolean
    ];
}
