<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackgroundStory extends Model
{
    use HasFactory;

    protected $table = 'background_story';

    protected $fillable = [
        'background_image_name',
        'background_image_path',
    ];
    public function settingsStories()
    {
        return $this->hasMany(SettingsStory::class, 'background_story_id');
    }
}
