<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'related_post_id',
        'is_read',
        'action_url',
        'preview_text',
        'is_email_sent',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_email_sent' => 'boolean',
        ];
    }

    public const TYPES = [
        'like',
        'repost',
        'quote',
        'reply',
        'follow',
        'mention',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function relatedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'related_post_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    public function getActionUrl(): string
    {
        return match ($this->type) {
            'like', 'repost', 'quote', 'mention' => "/posts/{$this->related_post_id}",
            'reply' => "/posts/{$this->related_post_id}#reply-{$this->id}",
            'follow' => "/users/{$this->actor_id}",
            default => '/notifications',
        };
    }

    public function getPreviewText(): string
    {
        return match ($this->type) {
            'like' => 'liked your post',
            'repost' => 'reposted your post',
            'quote' => 'quoted your post',
            'reply' => 'replied to your post',
            'follow' => 'started following you',
            'mention' => 'mentioned you in a post',
            default => 'interacted with you',
        };
    }
}
