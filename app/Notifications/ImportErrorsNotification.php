<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportErrorsNotification extends Notification
{
    use Queueable;

    public $title;

    public $message;

    public function __construct(private $entity, private $filePath, private $errors)
    {
        $this->title = "Action Required: Errors in Your {$this->entity} Import";
        $this->message = "The import process for {$this->entity} has completed with the following errors: ".count($this->errors);
    }

    public function via($notifiable)
    {
        return ['database', 'mail', WhatsappChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('Download Your Result', url($this->filePath));
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'file_path' => $this->filePath,
        ];
    }

    public function toWhatsapp($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'file_path' => $this->filePath,
        ];
    }
}
