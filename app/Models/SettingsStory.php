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
        'background_mode',
        'screen_mode', 
        'font_family',
        'font_size',
        'line_height',
        'hasSettings',
        'brightness',
        'mode',
        'yellow_light_mode',
        'background_style',
        'background_color',
        'selected_gradient',
        'background_opacity',
        'custom_background_style',
        'custom_background_color',
        'custom_selected_gradient',
        'custom_background_opacity',
    ];

    protected $casts = [
        'yellow_light_mode' => 'boolean',
        'hasSettings' => 'boolean',
        'background_opacity' => 'float',
        'custom_background_opacity' => 'float',
        'line_height' => 'float',
        'font_size' => 'integer',
        'brightness' => 'integer',
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