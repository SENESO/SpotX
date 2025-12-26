<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Reply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'post_id',
        'parent_reply_id',
        'content',
        'media_urls',
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
        
        static::creating(function ($reply) {
            if (empty($reply->uuid)) {
                $reply->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parentReply(): BelongsTo
    {
        return $this->belongsTo(Reply::class, 'parent_reply_id');
    }

    public function childReplies(): HasMany
    {
        return $this->hasMany(Reply::class, 'parent_reply_id');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_reply_id');
    }
}
