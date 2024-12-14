<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportCompleted extends Notification
{
    use Queueable;

    public function __construct(protected $title, protected $filePath) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Your '.$this->title.' export is complete!')
            ->action('Download Your Export', url($this->filePath));
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'file_path' => $this->filePath,
            'message' => 'Your '.$this->title.' export is complete!',
        ];
    }
}
