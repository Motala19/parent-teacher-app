<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewAnnouncement extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public Announcement $announcement, public array $channels = ['database'])
    {
    }

    public function via($notifiable): array
    {
        return $this->channels;
    }

    private function url($notifiable, bool $absolute): string
    {
        if ($notifiable->hasRole('teacher')) {
            return route('teacher.notices.index', [], $absolute);
        }

        return route('parent.announcements.index', [], $absolute);
    }

    private function icon(): string
    {
        return match ($this->announcement->type) {
            'newsletter', 'question_paper', 'assignment' => 'file',
            'urgent'                                     => 'bell',
            default                                      => 'megaphone',
        };
    }

    public function toDatabase($notifiable): array
    {
        $a = $this->announcement;

        return [
            'kind'            => 'announcement',
            'icon'            => $this->icon(),
            'announcement_id' => $a->id,
            'type'            => $a->type,
            'count'           => 1,
            'title'           => $a->typeLabel() . ': ' . $a->title,
            'body'            => Str::limit($a->body, 90),
            'where'           => $a->audienceLabel(),
            'url'             => $this->url($notifiable, false),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $a      = $this->announcement->loadMissing('user');
        $files  = $a->attachments()->count();
        $poster = $a->user?->name ?? 'The school';

        $where = match ($a->audience) {
            'school'   => 'the whole school',
            'teachers' => 'all teachers',
            'smt'      => 'the school management team',
            default    => $a->audienceLabel(),
        };

        $subject = $a->type === 'urgent'
            ? 'Urgent: ' . $a->title
            : 'New ' . Str::lower($a->typeLabel()) . ': ' . $a->title;

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . Str::before($notifiable->name, ' ') . ',')
            ->line($poster . ' posted to ' . $where . ':')
            ->line('**' . $a->title . '**')
            ->line(Str::limit($a->body, 300));

        if ($files > 0) {
            $mail->line($files . ' ' . Str::plural('file', $files) . ' attached. Log in to view or download ' . ($files === 1 ? 'it' : 'them') . '.');
        }

        return $mail
            ->action('View announcement', $this->url($notifiable, true))
            ->line('You can switch these emails off under Notification settings after you log in.');
    }
}