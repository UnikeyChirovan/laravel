<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVoteResponse extends Model
{
    protected $fillable = [
        'notification_vote_id',
        'user_id',
        'option_id'
    ];

    public function vote()
    {
        return $this->belongsTo(NotificationVote::class, 'notification_vote_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
