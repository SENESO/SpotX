<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PostMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'post_id',
        'media_url',
        'thumbnail_url',
        'media_type',
        'file_size',
        'mime_type',
        'width',
        'height',
        'duration',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }
}
