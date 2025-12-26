<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected string $fcmServerKey;
    protected string $fcmApiUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = config('services.fcm.server_key', env('FCM_SERVER_KEY', ''));
    }

    public function sendPushNotification(User $user, Notification $notification): bool
    {
        if (!$user->fcm_token) {
            return false;
        }

        $payload = $this->buildPayload($user, $notification);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmApiUrl, $payload);

            if ($response->failed()) {
                Log::error('FCM push notification failed', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'response' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('FCM push notification error', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendBulkPushNotifications(User $user, array $notificationIds): bool
    {
        if (!$user->fcm_token) {
            return false;
        }

        $notifications = Notification::whereIn('id', $notificationIds)
            ->where('user_id', $user->id)
            ->get();

        $payload = $this->buildMultiNotificationPayload($user, $notifications);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmApiUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM bulk push notification error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function buildPayload(User $user, Notification $notification): array
    {
        return [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => $this->getTitle($notification),
                'body' => $notification->preview_text,
                'icon' => $this->getIcon(),
                'click_action' => $this->getClickAction($notification),
                'tag' => $notification->type . '_' . ($notification->related_post_id ?? 'general'),
                'data' => [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                    'actor_id' => $notification->actor_id,
                    'related_post_id' => $notification->related_post_id,
                    'action_url' => $notification->action_url,
                ],
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'notifications',
                    'priority' => 'high',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                        'badge' => $this->getUnreadBadge($user),
                    ],
                ],
            ],
        ];
    }

    protected function buildMultiNotificationPayload(User $user, $notifications): array
    {
        $latest = $notifications->first();
        $count = $notifications->count();

        $title = $count > 1 
            ? "You have {$count} new notifications" 
            : 'New notification';

        $body = $count > 1
            ? "From {$notifications->pluck('actor.username')->unique()->take(2)->implode(', ')}"
            : $latest->preview_text;

        return [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => $this->getIcon(),
                'badge' => $this->getUnreadBadge($user),
            ],
            'data' => [
                'type' => 'multi_notification',
                'count' => $count,
                'notification_ids' => $notifications->pluck('id')->toArray(),
            ],
        ];
    }

    protected function getTitle(Notification $notification): string
    {
        return match ($notification->type) {
            'like' => 'New like',
            'repost' => 'New repost',
            'quote' => 'New quote',
            'reply' => 'New reply',
            'follow' => 'New follower',
            'mention' => 'New mention',
            default => 'New notification',
        };
    }

    protected function getIcon(): string
    {
        return config('app.url') . '/icons/notification-icon.png';
    }

    protected function getClickAction(Notification $notification): string
    {
        return $notification->action_url;
    }

    protected function getUnreadBadge(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function updateUserToken(User $user, string $token): void
    {
        $user->update(['fcm_token' => $token]);
    }
}
