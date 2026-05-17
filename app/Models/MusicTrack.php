<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'title',
        'artist',
        'file_path',
        'duration',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'duration' => 'integer',
        'album_id' => 'integer'
    ];

    protected $appends = ['file_url'];

    public function album()
    {
        return $this->belongsTo(MusicAlbum::class, 'album_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_favorite_tracks', 'track_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot('order');
    }

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return url('storage/' . $this->file_path);
        }
        return null;
    }
}