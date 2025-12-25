<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicAlbum extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cover_image',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    public function tracks()
    {
        return $this->hasMany(MusicTrack::class, 'album_id')->orderBy('order');
    }

    public function activeTracks()
    {
        return $this->hasMany(MusicTrack::class, 'album_id')
                    ->where('is_active', true)
                    ->orderBy('order');
    }
}