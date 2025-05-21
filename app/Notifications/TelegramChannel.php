<?php

namespace App\Notifications;

use App\Helpers\TelegramHelper;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    protected $telegram;

    public function __construct(TelegramHelper $telegram)
    {
        $this->telegram = $telegram;
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if ($notifiable->is_telegram_enabled) {
            $data = $notification->toTelegram($notifiable);
            $messageData = [
                'mobile' => $notifiable->mobile,
                'message' => $data['message'],
                'filePath' => isset($data['file_path']) ? public_path('storage/'.$data['file_path']) : null,
            ];

            $response = $this->telegram->send($messageData);
            if (! $response['success']) {
                info($response);
            }
        }
    }
}
