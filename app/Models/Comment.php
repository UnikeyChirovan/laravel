<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'parent_id',
        'content',
    ];

    protected $with = ['user'];

    // Relationship: User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'nickname', 'avatar']);
    }

    // Relationship: Parent Comment
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Relationship: Replies (children) - sắp xếp từ cũ nhất đến mới nhất
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->oldest();
    }

    // Check if comment is a reply
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    // Scope: Get only parent comments
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope: Get comments for specific chapter
    public function scopeForChapter($query, $chapterId)
    {
        return $query->where('commentable_type', 'chapter')
                     ->where('commentable_id', $chapterId);
    }

    // Scope: Get comments for specific episode
    public function scopeForEpisode($query, $episodeId)
    {
        return $query->where('commentable_type', 'episode')
                     ->where('commentable_id', $episodeId);
    }
}