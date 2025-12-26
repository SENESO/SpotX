<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Mention extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'post_id',
        'user_id',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($mention) {
            if (empty($mention->uuid)) {
                $mention->uuid = (string) Str::uuid();
            }
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
