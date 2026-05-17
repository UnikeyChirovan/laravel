<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageManager extends Model
{
    use HasFactory;

    protected $table = 'image_manager';

    protected $fillable = [
        'image_name',
        'image_path',
    ];

    public function settingsStories()
    {
        return $this->hasMany(SettingsStory::class, 'image_manager_id');
    }
}
