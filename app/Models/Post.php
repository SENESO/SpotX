<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'content',
        'media_urls',
        'engagement_count',
        'likes_count',
        'reposts_count',
        'quotes_count',
        'replies_count',
        'views_count',
        'visibility',
        'original_post_uuid',
    ];

    protected function casts(): array
    {
        return [
            'media_urls' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($post) {
            if (empty($post->uuid)) {
                $post->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function likes(): HasMany
    {
        return $this->interactions()->where('interaction_type', 'like');
    }

    public function reposts(): HasMany
    {
        return $this->interactions()->where('interaction_type', 'repost');
    }

    public function quotes(): HasMany
    {
        return $this->interactions()->where('interaction_type', 'quote');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class);
    }

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'original_post_uuid', 'uuid');
    }

    public function quotedPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'original_post_uuid', 'uuid');
    }

    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->is_suspended) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
              ->orWhere(function ($sub) use ($user) {
                  $sub->where('visibility', 'followers_only')
                      ->whereIn('user_id', $user->following()->select('following_id'));
              })
              ->orWhere('user_id', $user->id);
        })
        ->whereDoesntHave('user.blockedBy', function ($q) use ($user) {
            $q->where('blocker_id', $user->id);
        });
    }

    public function scopeExcludingBlocked($query, User $user)
    {
        return $query->whereDoesntHave('user.blockedBy', function ($q) use ($user) {
            $q->where('blocker_id', $user->id);
        })
        ->whereDoesntHave('user.blocks', function ($q) use ($user) {
            $q->where('blocked_id', $user->id);
        });
    }

    public function scopeExcludingSuspended($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_suspended', false);
        });
    }

    public function scopeFromFollowing($query, User $user)
    {
        return $query->whereIn('user_id', $user->following()->select('following_id'));
    }

    public function scopeWithHighEngagement($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderByRaw('(likes_count + reposts_count * 2 + quotes_count * 3) DESC');
    }

    public function scopeWithRecentPosts($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->visibility === 'public') {
            return !$this->user->isBlockedBy($user) && !$user->isBlockedBy($this->user);
        }

        if ($this->visibility === 'followers_only') {
            return $user->isFollowing($this->user) && 
                   !$this->user->isBlockedBy($user) && 
                   !$user->isBlockedBy($this->user);
        }

        return false;
    }
}
