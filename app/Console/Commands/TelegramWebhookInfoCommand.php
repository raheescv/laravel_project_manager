<?php

namespace App\Console\Commands;

use App\Helpers\TelegramHelper;
use Illuminate\Console\Command;

class TelegramWebhookInfoCommand extends Command
{
    protected $signature = 'telegram:webhook-info';

    protected $description = 'Show current Telegram webhook URL and delivery status';

    public function handle(TelegramHelper $telegram): int
    {
        $info = $telegram->getWebhookInfo();
        if (! $info) {
            $this->error('Could not get webhook info. Check TELEGRAM_BOT_TOKEN in .env');

            return self::FAILURE;
        }

        $url = $info->url ?? '';
        $expectedUrl = config('app.url').'/api/telegram/webhook';

        $this->info('Current webhook URL (from Telegram): '.($url ?: '(not set)'));
        $this->info('Expected URL (from APP_URL): '.$expectedUrl);
        $this->newLine();

        if ($url !== $expectedUrl) {
            $this->warn('URLs do not match. Run: php artisan telegram:setup');
            $this->newLine();
        }

        if ($url) {
            $pending = $info->pending_update_count ?? 0;
            $this->info("Pending updates: {$pending}");
            if ($pending > 0) {
                $this->comment('Telegram is trying to deliver updates. If your app never receives them, the URL may be unreachable (firewall, HTTPS, or wrong domain).');
            }

            $lastError = $info->last_error_message ?? null;
            $lastErrorDate = $info->last_error_date ?? null;
            if ($lastError) {
                $this->newLine();
                $this->error('Last delivery error from Telegram:');
                $this->error($lastError);
                if ($lastErrorDate) {
                    $this->error('At: '.date('Y-m-d H:i:s', $lastErrorDate));
                }
            }
        }

        return self::SUCCESS;
    }
}
