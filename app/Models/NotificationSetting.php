<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id',
        'notify_likes',
        'notify_reposts',
        'notify_quotes',
        'notify_replies',
        'notify_mentions',
        'notify_follows',
        'notify_follower_posts',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'email_digest_enabled',
        'email_digest_frequency',
    ];

    protected function casts(): array
    {
        return [
            'notify_likes' => 'boolean',
            'notify_reposts' => 'boolean',
            'notify_quotes' => 'boolean',
            'notify_replies' => 'boolean',
            'notify_mentions' => 'boolean',
            'notify_follows' => 'boolean',
            'notify_follower_posts' => 'boolean',
            'quiet_hours_enabled' => 'boolean',
            'email_digest_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isNotificationEnabled(string $type): bool
    {
        return match ($type) {
            'like' => $this->notify_likes,
            'repost' => $this->notify_reposts,
            'quote' => $this->notify_quotes,
            'reply' => $this->notify_replies,
            'mention' => $this->notify_mentions,
            'follow' => $this->notify_follows,
            default => false,
        };
    }

    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled) {
            return false;
        }

        $now = now()->format('H:i:s');
        return $now >= $this->quiet_hours_start && $now <= $this->quiet_hours_end;
    }

    public static function getDefaultForUser(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
        ]);
    }
}
