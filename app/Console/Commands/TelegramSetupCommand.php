<?php

namespace App\Console\Commands;

use App\Helpers\TelegramHelper;
use Illuminate\Console\Command;

class TelegramSetupCommand extends Command
{
    protected $signature = 'telegram:setup';

    protected $description = 'Set up Telegram webhook URL';

    public function handle(TelegramHelper $telegram): void
    {
        $webhookUrl = config('app.url').'/api/telegram/webhook';
        $response = $telegram->setupWebhook($webhookUrl);
        if ($response['success']) {
            $this->info('Telegram webhook set up successfully!');
            $this->info("Webhook URL: {$webhookUrl}");
        } else {
            $this->error('Failed to set up Telegram webhook: '.$response['message']);
        }
    }
}
