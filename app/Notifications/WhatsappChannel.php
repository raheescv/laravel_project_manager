<?php

namespace App\Notifications;

use App\Helpers\Facades\WhatsappHelper;
use Illuminate\Notifications\Notification;

class WhatsappChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if ($notifiable->is_whatsapp_enabled) {
            $data = $notification->toWhatsapp($notifiable);
            $messageData = [
                'number' => $notifiable->mobile,
                'message' => $data['message'],
                'filePath' => public_path('storage/'.$data['file_path']),
            ];
            $response = WhatsappHelper::send($messageData);
            if (! $response['success']) {
                info($response);
            }
        }
    }
}
