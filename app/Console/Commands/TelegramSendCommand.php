<?php

namespace App\Console\Commands;

use App\Helpers\TelegramHelper;
use Illuminate\Console\Command;
use App\Models\Configuration;

class TelegramSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send
                            {mobile : The user mobile number (with country code, e.g. 971501234567)}
                            {message : The message text to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a Telegram message to a user by mobile number (user must have connected the bot)';

    /**
     * Execute the console command.
     */
    public function handle(TelegramHelper $telegram): int
    {
        $mobile = $this->argument('mobile');
        $message = $this->argument('message');

        $response = $telegram->send(['mobile' => $mobile, 'message' => $message]);

        if ($response['success']) {
            $this->info('Message sent successfully!');

            return self::SUCCESS;
        }

        $this->error("Failed to send message: {$response['message']}");
        if (str_contains($response['message'], 'needs to connect')) {
            $this->info("\nTo connect the user:");
            $this->info('1. Ask them to open your Telegram bot: @'.Configuration::where('key', 'telegram_bot_username')->value('value') ?? 'YourBot');
            $this->info('2. They should share their contact information when prompted');
            $this->info("3. Try sending the message again after they've connected");
        }

        return self::FAILURE;
    }
}
