<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendNotificationEmail(User $user, Notification $notification): bool
    {
        try {
            Mail::to($user->email)->send(
                new \App\Mail\NotificationMail($notification, $user)
            );
            return true;
        } catch (\Exception $e) {
            \Log::error('Email notification failed', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendDailyDigest(User $user): bool
    {
        $notifications = Notification::query()
            ->with(['actor', 'relatedPost'])
            ->where('user_id', $user->id)
            ->where('is_email_sent', false)
            ->where('created_at', '>=', now()->subDay())
            ->get();

        if ($notifications->isEmpty()) {
            return true;
        }

        try {
            Mail::to($user->email)->send(
                new \App\Mail\DailyDigestMail($notifications, $user)
            );

            $notifications->each(function ($n) {
                $n->update(['is_email_sent' => true]);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Daily digest email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendWeeklyDigest(User $user): bool
    {
        $notifications = Notification::query()
            ->with(['actor', 'relatedPost'])
            ->where('user_id', $user->id)
            ->where('is_email_sent', false)
            ->where('created_at', '>=', now()->subWeek())
            ->get();

        if ($notifications->isEmpty()) {
            return true;
        }

        try {
            Mail::to($user->email)->send(
                new \App\Mail\WeeklyDigestMail($notifications, $user)
            );

            $notifications->each(function ($n) {
                $n->update(['is_email_sent' => true]);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Weekly digest email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
