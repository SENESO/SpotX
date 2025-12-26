<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );

        return response()->json([
            'settings' => [
                'notify_likes' => $settings->notify_likes,
                'notify_reposts' => $settings->notify_reposts,
                'notify_quotes' => $settings->notify_quotes,
                'notify_replies' => $settings->notify_replies,
                'notify_mentions' => $settings->notify_mentions,
                'notify_follows' => $settings->notify_follows,
                'notify_follower_posts' => $settings->notify_follower_posts,
                'quiet_hours_enabled' => $settings->quiet_hours_enabled,
                'quiet_hours_start' => $settings->quiet_hours_start,
                'quiet_hours_end' => $settings->quiet_hours_end,
                'email_digest_enabled' => $settings->email_digest_enabled,
                'email_digest_frequency' => $settings->email_digest_frequency,
            ],
        ]);
    }

    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );

        $settings->update($request->validated());

        return response()->json([
            'message' => 'Notification settings updated',
            'settings' => [
                'notify_likes' => $settings->notify_likes,
                'notify_reposts' => $settings->notify_reposts,
                'notify_quotes' => $settings->notify_quotes,
                'notify_replies' => $settings->notify_replies,
                'notify_mentions' => $settings->notify_mentions,
                'notify_follows' => $settings->notify_follows,
                'notify_follower_posts' => $settings->notify_follower_posts,
                'quiet_hours_enabled' => $settings->quiet_hours_enabled,
                'quiet_hours_start' => $settings->quiet_hours_start,
                'quiet_hours_end' => $settings->quiet_hours_end,
                'email_digest_enabled' => $settings->email_digest_enabled,
                'email_digest_frequency' => $settings->email_digest_frequency,
            ],
        ]);
    }

    public function updatePushSettings(Request $request): JsonResponse
    {
        $request->validate([
            'notify_likes' => ['sometimes', 'boolean'],
            'notify_reposts' => ['sometimes', 'boolean'],
            'notify_quotes' => ['sometimes', 'boolean'],
            'notify_replies' => ['sometimes', 'boolean'],
            'notify_mentions' => ['sometimes', 'boolean'],
            'notify_follows' => ['sometimes', 'boolean'],
            'notify_follower_posts' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );

        $settings->update($request->only([
            'notify_likes',
            'notify_reposts',
            'notify_quotes',
            'notify_replies',
            'notify_mentions',
            'notify_follows',
            'notify_follower_posts',
        ]));

        return response()->json([
            'message' => 'Push notification settings updated',
        ]);
    }

    public function updateQuietHours(Request $request): JsonResponse
    {
        $request->validate([
            'quiet_hours_enabled' => ['sometimes', 'boolean'],
            'quiet_hours_start' => ['sometimes', 'date_format:H:i:s'],
            'quiet_hours_end' => ['sometimes', 'date_format:H:i:s'],
        ]);

        $user = $request->user();
        
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );

        $settings->update($request->only([
            'quiet_hours_enabled',
            'quiet_hours_start',
            'quiet_hours_end',
        ]));

        return response()->json([
            'message' => 'Quiet hours settings updated',
            'quiet_hours' => [
                'enabled' => $settings->quiet_hours_enabled,
                'start' => $settings->quiet_hours_start,
                'end' => $settings->quiet_hours_end,
                'is_currently_active' => $settings->isInQuietHours(),
            ],
        ]);
    }

    public function updateEmailSettings(Request $request): JsonResponse
    {
        $request->validate([
            'email_digest_enabled' => ['sometimes', 'boolean'],
            'email_digest_frequency' => ['sometimes', 'string', 'in:never,daily,weekly'],
        ]);

        $user = $request->user();
        
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );

        $settings->update($request->only([
            'email_digest_enabled',
            'email_digest_frequency',
        ]));

        return response()->json([
            'message' => 'Email notification settings updated',
        ]);
    }
}
