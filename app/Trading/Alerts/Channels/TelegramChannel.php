<?php

namespace App\Trading\Alerts\Channels;

use App\Trading\Contracts\AlertChannel;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramChannel implements AlertChannel
{
    public function code(): string
    {
        return 'telegram';
    }

    public function send(string $title, string $body, array $payload = []): bool
    {
        $chatId = config('trading.alerts.telegram_chat_id') ?? config('services.telegram.chat_id');
        if (! $chatId) {
            return false;
        }

        try {
            $severity = strtoupper($payload['severity'] ?? 'info');
            $text = "*[{$severity}] {$title}*\n{$body}";
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram alert failed', ['err' => $e->getMessage()]);

            return false;
        }
    }
}
