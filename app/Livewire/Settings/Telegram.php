<?php

namespace App\Livewire\Settings;

use App\Helpers\TelegramHelper;
use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Telegram extends Component
{
    public ?string $commandOutput = null;

    public ?int $commandExitCode = null;

    public ?string $webhookUrl = null;

    public ?string $botUsername = null;

    public ?string $botToken = null;

    public ?string $testMobile = null;

    public ?string $testMessage = null;

    public ?string $sendResult = null;

    public function mount(): void
    {
        $this->webhookUrl = config('app.url').'/api/telegram/webhook';
        $this->botUsername = config('services.telegram.bot_username');
        $this->botToken = config('services.telegram.bot_token');
    }

    public function saveCredentials(): void
    {
        $this->validate([
            'botToken' => 'nullable|string|max:255',
            'botUsername' => 'nullable|string|max:64',
        ]);

        if ($this->botToken !== null && $this->botToken !== '') {
            Configuration::updateOrCreate(['key' => 'telegram_bot_token'], ['value' => $this->botToken]);
            writeToEnv('TELEGRAM_BOT_TOKEN', $this->botToken);
        }
        if ($this->botUsername !== null && $this->botUsername !== '') {
            Configuration::updateOrCreate(['key' => 'telegram_bot_username'], ['value' => trim($this->botUsername)]);
            writeToEnv('TELEGRAM_BOT_USERNAME', trim($this->botUsername));
        }

        Artisan::call('optimize:clear');
        $this->dispatch('success', ['message' => 'Telegram credentials saved to configuration.']);
    }

    /**
     * Run the telegram:setup command (registers webhook with Telegram).
     */
    public function runSetup(): void
    {
        $this->commandOutput = null;
        $this->commandExitCode = Artisan::call('telegram:setup');
        $this->commandOutput = trim(Artisan::output());

        if ($this->commandExitCode === 0) {
            $this->dispatch('success', ['message' => 'Telegram webhook set up successfully.']);
        } else {
            $this->dispatch('error', ['message' => 'Setup failed. Check .env and command output below.']);
        }
    }

    /**
     * Send a test Telegram message (same as php artisan telegram:send).
     */
    public function sendTestMessage(TelegramHelper $telegram): void
    {
        $this->validate([
            'testMobile' => 'required|string|max:32',
            'testMessage' => 'required|string|max:4096',
        ]);

        $this->sendResult = null;
        $response = $telegram->send([
            'mobile' => $this->testMobile,
            'message' => $this->testMessage,
        ]);

        if ($response['success']) {
            $this->dispatch('success', ['message' => 'Test message sent successfully.']);
            $this->sendResult = 'success';
        } else {
            $this->dispatch('error', ['message' => $response['message']]);
            $this->sendResult = $response['message'];
        }
    }

    /**
     * Run the telegram:webhook-info command (shows current webhook status).
     */
    public function runWebhookInfo(): void
    {
        $this->commandOutput = null;
        $this->commandExitCode = Artisan::call('telegram:webhook-info');
        $this->commandOutput = trim(Artisan::output());

        if ($this->commandExitCode === 0) {
            $this->dispatch('success', ['message' => 'Webhook info retrieved.']);
        } else {
            $this->dispatch('error', ['message' => 'Could not get webhook info. Check TELEGRAM_BOT_TOKEN.']);
        }
    }

    /**
     * Get webhook info from Telegram API (for status display).
     */
    public function getWebhookStatusProperty(TelegramHelper $telegram): ?array
    {
        $info = $telegram->getWebhookInfo();
        if (! $info) {
            return null;
        }

        return [
            'url' => $info->url ?? '(not set)',
            'pending_updates' => $info->pending_update_count ?? 0,
            'last_error' => $info->last_error_message ?? null,
        ];
    }

    public function render()
    {
        return view('livewire.settings.telegram');
    }
}
