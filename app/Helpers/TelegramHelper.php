<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Exception;

class TelegramHelper
{
    private Api $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function send(array $data)
    {
        try {
            if (empty($data['mobile'])) {
                throw new Exception('Mobile number is required');
            }

            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($data['mobile']);

            // Get or create user with telegram chat ID
            $user = User::where('mobile', $formattedPhone)->first();
            if (! $user) {
                // If user doesn't exist, we'll send them a message to connect
                $this->inviteUserToConnect($formattedPhone);
                throw new Exception('User needs to connect with Telegram bot first. Invitation sent.');
            }

            // If user exists but no chat ID and not in auto-connect mode, notify them
            if (! $user->telegram_chat_id) {
                throw new Exception('User needs to connect with the bot first. Please ask them to message @'.config('services.telegram.bot_username'));
            }

            $this->telegram->sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $data['message'],
            ]);
            // If there's a file to send
            if (! empty($data['filePath']) && file_exists($data['filePath'])) {
                $this->telegram->sendDocument([
                    'chat_id' => $user->telegram_chat_id,
                    'document' => $data['filePath'],
                ]);
            }

            return ['success' => true, 'message' => 'Message sent successfully'];
        } catch (Exception $e) {
            Log::error('Telegram send failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a document (e.g. PDF) to a Telegram chat by chat ID.
     * Use for channels/groups or when chat_id is known (e.g. daily report).
     */
    public function sendDocumentToChatId(string $chatId, string $filePath, ?string $caption = null): array
    {
        try {
            if (! file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            $params = [
                'chat_id' => $chatId,
                'document' => $filePath,
            ];
            if ($caption !== null && $caption !== '') {
                $params['caption'] = $caption;
            }

            $this->telegram->sendDocument($params);

            return ['success' => true, 'message' => 'Document sent successfully'];
        } catch (Exception $e) {
            Log::error('Telegram sendDocumentToChatId failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function inviteUserToConnect(string $phone)
    {
        try {
            // Create a deep link for the bot
            $botUsername = config('services.telegram.bot_username');
            $inviteMessage = "Please connect with our Telegram bot to receive messages: https://t.me/{$botUsername}\n".
                           'After opening the bot, please share your contact information when prompted.';
            // You could send this message via SMS or other channels
            // For now we'll just log it
            Log::info("Invitation sent to $phone: $inviteMessage");

        } catch (Exception $e) {
            Log::error('Failed to send bot invitation: '.$e->getMessage());
        }
    }

    public function setupWebhook($url)
    {
        try {
            $response = $this->telegram->setWebhook(['url' => $url]);

            return ['success' => true, 'message' => 'Webhook setup successfully'];
        } catch (TelegramSDKException $e) {
            Log::error('Telegram webhook setup failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get current webhook info from Telegram (URL, pending updates, last error).
     */
    public function getWebhookInfo(): ?\Telegram\Bot\Objects\WebhookInfo
    {
        try {
            return $this->telegram->getWebhookInfo();
        } catch (\Throwable $e) {
            Log::error('Telegram getWebhookInfo failed: '.$e->getMessage());

            return null;
        }
    }

    public function handleUpdate($update)
    {
        try {
            $message = $update['message'] ?? null;
            if (! $message) {
                return;
            }

            $chatId = $message['chat']['id'] ?? null;
            $phoneNumber = $message['contact']['phone_number'] ?? null;
            $text = $message['text'] ?? '';

            if ($chatId && $phoneNumber) {
                // Format phone number to match your database format
                $formattedPhone = $this->formatPhoneNumber($phoneNumber);

                // Update or create user with telegram chat id
                $user = User::where('mobile', $formattedPhone)->first();
                if (! $user) {
                    throw new Exception("User not found for this mobile no: $formattedPhone");
                }
                $user->update([
                    'telegram_chat_id' => $chatId,
                    'is_telegram_enabled' => true,
                ]);

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Successfully connected your Telegram account!',
                ]);

                return;
            }

            // Reply with chat ID and show "Share contact" button for /start or /chatid
            $textLower = strtolower(trim((string) $text));
            $wantsChatId = $textLower === '' || str_starts_with($textLower, '/start') || str_starts_with($textLower, '/chatid');
            if ($chatId && $wantsChatId) {
                try {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Your Telegram Chat ID: {$chatId}\n\n".
                            'Tap the button below to share your phone number and link your account for notifications.',
                        'reply_markup' => json_encode([
                            'keyboard' => [
                                [['text' => '📱 Share my phone number', 'request_contact' => true]],
                            ],
                            'one_time_keyboard' => true,
                            'resize_keyboard' => true,
                        ]),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Telegram sendMessage failed: '.$e->getMessage(), ['chat_id' => $chatId]);
                }
            }
        } catch (Exception $e) {
            Log::error('Telegram update handling failed: '.$e->getMessage());
        }
    }

    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add '+' prefix if not present
        if (! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }
}
