<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationVote extends Model
{
    protected $fillable = [
        'notification_id',
        'question',
        'options'
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function notification()
    {
        return $this->belongsTo(UserNotification::class, 'notification_id');
    }

    public function responses()
    {
        return $this->hasMany(UserVoteResponse::class, 'notification_vote_id');
    }

    public function getResultsAttribute()
    {
        $total = $this->responses()->count();
        
        if ($total === 0) {
            return array_map(function($option) {
                return [
                    'id' => $option['id'],
                    'text' => $option['text'],
                    'count' => 0,
                    'percentage' => 0
                ];
            }, $this->options);
        }

        $counts = $this->responses()
            ->selectRaw('option_id, COUNT(*) as count')
            ->groupBy('option_id')
            ->pluck('count', 'option_id')
            ->toArray();

        return array_map(function($option) use ($counts, $total) {
            $count = $counts[$option['id']] ?? 0;
            return [
                'id' => $option['id'],
                'text' => $option['text'],
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1)
            ];
        }, $this->options);
    }
}
