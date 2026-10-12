<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public Message $message, public array $channels = ['database'])
    {
    }

    public function via($notifiable): array
    {
        return $this->channels;
    }

    public function toDatabase($notifiable): array
    {
        $sender = $this->message->sender;

        return [
            'kind'      => 'message',
            'icon'      => 'chat',
            'sender_id' => $sender->id,
            'count'     => 1,
            'title'     => 'New message from ' . $sender->name,
            'body'      => Str::limit($this->message->body ?: 'Sent an attachment', 90),
            'url'       => route('messages.show', $sender, false),
        ];
    }

    // Message text is deliberately left out of emails for privacy
    public function toMail($notifiable): MailMessage
    {
        $sender  = $this->message->sender;
        $student = $this->message->student;

        return (new MailMessage)
            ->subject('New message from ' . $sender->name)
            ->greeting('Hello ' . Str::before($notifiable->name, ' ') . ',')
            ->line($sender->name . ' sent you a message' . ($student ? ' about ' . $student->first_name . ' ' . $student->last_name : '') . '.')
            ->line('For privacy, messages are not shown in emails. Log in to read and reply.')
            ->action('Open the chat', route('messages.show', $sender))
            ->line('You can switch these emails off under Notification settings after you log in.');
    }
}