<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'interaction_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeLikes($query)
    {
        return $query->where('interaction_type', 'like');
    }

    public function scopeReposts($query)
    {
        return $query->where('interaction_type', 'repost');
    }

    public function scopeQuotes($query)
    {
        return $query->where('interaction_type', 'quote');
    }
}
