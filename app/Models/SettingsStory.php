<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingsStory extends Model
{
    use HasFactory;

    protected $table = 'settings_story';

    protected $fillable = [
        'user_id',
        'background_story_id',
        'font_family',
        'font_size',
        'line_height',
        'hasSettings',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function backgroundStory()
    {
        return $this->belongsTo(BackgroundStory::class, 'background_story_id');
    }
}
