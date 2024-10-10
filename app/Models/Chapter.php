<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = ['title', 'story_name', 'author', 'chapter_number', 'file_path'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chapter) {
            if (empty($chapter->story_name)) {
                $chapter->story_name = 'THẤT SẮC CHI ĐẠO';
            }

            if (empty($chapter->author)) {
                $chapter->author = 'Hoanganh Pham';
            }
        });
    }
}
