<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ParentInvitation extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes  = (int) config('auth.passwords.' . config('auth.defaults.passwords', 'users') . '.expire', 60);
        $validFor = $minutes >= 1440
            ? round($minutes / 1440) . ' day(s)'
            : max(1, round($minutes / 60)) . ' hour(s)';

        $school = $notifiable->school?->name ?? 'Your school';

        return (new MailMessage)
            ->subject("Welcome to {$school}'s parent portal")
            ->greeting('Hello ' . Str::before($notifiable->name, ' ') . ',')
            ->line("{$school} has created a parent account for you. You can read announcements and newsletters, see homework and message your child's teachers.")
            ->line('Click the button below to choose your password.')
            ->action('Set my password', $url)
            ->line('After that, log in with this email address: ' . $notifiable->email)
            ->line("This link works for {$validFor}. If it expires, use \"Forgot your password?\" on the login page.")
            ->line('If you were not expecting this email, you can ignore it.');
    }
}