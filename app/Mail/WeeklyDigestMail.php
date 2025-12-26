<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $notifications;
    public User $user;

    public function __construct(Collection $notifications, User $user)
    {
        $this->notifications = $notifications;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your weekly notification digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-digest',
            with: [
                'user' => $this->user,
                'notifications' => $this->notifications,
                'count' => $this->notifications->count(),
            ],
        );
    }
}
