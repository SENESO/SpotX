<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Collection;

class NotificationService
{
    protected PushNotificationService $pushService;
    protected EmailService $emailService;

    public function __construct(
        PushNotificationService $pushService = null,
        EmailService $emailService = null
    ) {
        $this->pushService = $pushService ?? new PushNotificationService();
        $this->emailService = $emailService ?? new EmailService();
    }

    public function createNotification(
        User $recipient,
        User $actor,
        string $type,
        ?Post $relatedPost = null
    ): Notification {
        if ($recipient->id === $actor->id) {
            return Notification::make();
        }

        $setting = $this->getOrCreateSettings($recipient);

        if (!$this->shouldNotify($setting, $type)) {
            return Notification::make();
        }

        $notification = Notification::create([
            'user_id' => $recipient->id,
            'actor_id' => $actor->id,
            'type' => $type,
            'related_post_id' => $relatedPost?->id,
            'is_read' => false,
            'action_url' => $this->generateActionUrl($type, $relatedPost),
            'preview_text' => $this->generatePreviewText($actor, $type),
        ]);

        $this->sendNotification($notification, $setting);

        return $notification;
    }

    public function createBulkNotifications(
        array $recipientIds,
        User $actor,
        string $type,
        ?Post $relatedPost = null
    ): Collection
    {
        $notifications = collect();
        $setting = $this->getOrCreateSettings(User::find($recipientIds[0]));

        foreach ($recipientIds as $recipientId) {
            $recipient = User::find($recipientId);
            
            if (!$recipient || $recipient->id === $actor->id) {
                continue;
            }

            $userSetting = $this->getOrCreateSettings($recipient);
            
            if (!$this->shouldNotify($userSetting, $type)) {
                continue;
            }

            $notification = Notification::create([
                'user_id' => $recipientId,
                'actor_id' => $actor->id,
                'type' => $type,
                'related_post_id' => $relatedPost?->id,
                'is_read' => false,
                'action_url' => $this->generateActionUrl($type, $relatedPost),
                'preview_text' => $this->generatePreviewText($actor, $type),
            ]);

            $notifications->push($notification);
            $this->sendNotification($notification, $userSetting);
        }

        return $notifications;
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->update(['is_read' => true]);
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function markMultipleAsRead(User $user, array $notificationIds): int
    {
        return Notification::where('user_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->update(['is_read' => true]);
    }

    public function deleteNotification(Notification $notification): void
    {
        $notification->delete();
    }

    public function deleteMultiple(User $user, array $notificationIds): int
    {
        return Notification::where('user_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->delete();
    }

    public function clearAll(User $user): int
    {
        return Notification::where('user_id', $user->id)->delete();
    }

    public function getNotifications(
        User $user,
        int $limit = 20,
        ?int $afterId = null,
        ?string $type = null
    ): Collection
    {
        $query = Notification::query()
            ->with(['actor', 'relatedPost'])
            ->where('user_id', $user->id);

        if ($afterId) {
            $query->where('id', '<', $afterId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUnreadCount(User $user, ?string $type = null): int
    {
        $query = Notification::where('user_id', $user->id)
            ->where('is_read', false);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->count();
    }

    public function getUnreadCountsByType(User $user): array
    {
        $counts = [];
        
        foreach (Notification::TYPES as $type) {
            $counts[$type] = $this->getUnreadCount($user, $type);
        }

        return $counts;
    }

    protected function getOrCreateSettings(User $user): NotificationSetting
    {
        return NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );
    }

    protected function shouldNotify(NotificationSetting $setting, string $type): bool
    {
        if ($setting->isInQuietHours()) {
            return false;
        }

        return $setting->isNotificationEnabled($type);
    }

    protected function sendNotification(Notification $notification, NotificationSetting $setting): void
    {
        $recipient = $notification->user;
        
        $this->pushService->sendPushNotification($recipient, $notification);
        
        if ($this->shouldSendEmail($setting, $notification->type)) {
            $this->emailService->sendNotificationEmail($recipient, $notification);
            $notification->update(['is_email_sent' => true]);
        }
    }

    protected function shouldSendEmail(NotificationSetting $setting, string $type): bool
    {
        if (!$setting->email_digest_enabled) {
            return false;
        }

        if ($setting->email_digest_frequency === 'never') {
            return false;
        }

        return true;
    }

    protected function generateActionUrl(string $type, ?Post $post): string
    {
        return match ($type) {
            'like', 'repost', 'quote', 'mention' => $post ? "/posts/{$post->id}" : '/notifications',
            'reply' => $post ? "/posts/{$post->id}" : '/notifications',
            'follow' => '/notifications',
            default => '/notifications',
        };
    }

    protected function generatePreviewText(User $actor, string $type): string
    {
        return match ($type) {
            'like' => "{$actor->username} liked your post",
            'repost' => "{$actor->username} reposted your post",
            'quote' => "{$actor->username} quoted your post",
            'reply' => "{$actor->username} replied to your post",
            'follow' => "{$actor->username} started following you",
            'mention' => "{$actor->username} mentioned you",
            default => "{$actor->username} interacted with you",
        };
    }

    public function groupSimilarNotifications(User $user, int $hours = 24): Collection
    {
        $recentNotifications = Notification::query()
            ->with(['actor', 'relatedPost'])
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours($hours))
            ->where('type', '!=', 'follow')
            ->orderBy('created_at', 'desc')
            ->get();

        return $recentNotifications->groupBy(function ($notification) {
            if ($notification->related_post_id) {
                return $notification->type . '_' . $notification->related_post_id;
            }
            return $notification->type . '_' . $notification->actor_id;
        })->map(function ($group) {
            $first = $group->first();
            $count = $group->count();
            
            return [
                'id' => $first->id,
                'type' => $first->type,
                'related_post_id' => $first->related_post_id,
                'actors' => $group->pluck('actor')->unique('id')->values(),
                'count' => $count,
                'is_read' => $group->every(fn($n) => $n->is_read),
                'created_at' => $first->created_at,
                'preview_text' => $count > 1 
                    ? "{$group->first()->actor->username} and {$count} others"
                    : $first->preview_text,
            ];
        })->sortByDesc('created_at');
    }
}
