<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Notification $notification;
    public User $user;

    public function __construct(Notification $notification, User $user)
    {
        $this->notification = $notification;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notification',
            with: [
                'user' => $this->user,
                'notification' => $this->notification,
                'actor' => $this->notification->actor,
                'previewText' => $this->notification->preview_text,
                'actionUrl' => $this->notification->action_url,
            ],
        );
    }

    protected function getSubject(): string
    {
        return match ($this->notification->type) {
            'like' => 'Someone liked your post',
            'repost' => 'Someone reposted your post',
            'quote' => 'Someone quoted your post',
            'reply' => 'Someone replied to your post',
            'follow' => 'You have a new follower',
            'mention' => 'Someone mentioned you',
            default => 'New notification',
        };
    }
}
