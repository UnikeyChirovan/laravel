<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavoriteTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'track_id',
        'order'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'track_id' => 'integer',
        'order' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function track()
    {
        return $this->belongsTo(MusicTrack::class);
    }
}