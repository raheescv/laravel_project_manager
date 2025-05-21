<?php

namespace App\Console\Commands;

use App\Helpers\TelegramHelper;
use Illuminate\Console\Command;

class TelegramSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send {mobile} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a Telegram message using mobile number';

    /**
     * Execute the console command.
     */
    public function handle(TelegramHelper $telegram)
    {
        $mobile = $this->argument('mobile');
        $message = $this->argument('message');

        $response = $telegram->send([
            'mobile' => $mobile,
            'message' => $message,
        ]);

        if ($response['success']) {
            $this->info('Message sent successfully!');
        } else {
            $this->error("Failed to send message: {$response['message']}");
            if (str_contains($response['message'], 'needs to connect')) {
                $this->info("\nTo connect the user:");
                $this->info('1. Ask them to open your Telegram bot: @'.config('services.telegram.bot_username'));
                $this->info('2. They should share their contact information when prompted');
                $this->info("3. Try sending the message again after they've connected");
            }
        }
    }
}
