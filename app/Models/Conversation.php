<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'subject',
        'last_message_preview',
        'last_message_at',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($conversation) {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->whereNull('conversation_participants.left_at')
            ->withTimestamps(['joined_at']);
    }

    public function allParticipants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withTimestamps(['joined_at', 'left_at']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_at', 'created_at')
            ->whereHas('conversation', function ($q) {
                $q->whereColumn('conversations.id', 'conversation_id');
            });
    }

    public function scopeWithParticipant($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }

    public function getUnreadCountForUser(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        
        if (!$participant || !$participant->last_read_at) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('created_at', '>', $participant->last_read_at)
            ->where('user_id', '!=', $userId)
            ->count();
    }

    public function isParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function addParticipant(User $user): void
    {
        $this->participants()->syncWithoutDetaching([$user->id]);
    }

    public function removeParticipant(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);
    }

    public function markAsReadForUser(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);
    }
}
