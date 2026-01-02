<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappHelper
{
    protected string $accessToken;

    protected string $apiVersion;

    protected string $baseUrl;

    public $http;

    public function __construct()
    {
        $this->accessToken = config('services.meta_whatsapp.access_token');
        $this->baseUrl = config('services.meta_whatsapp.base_url');

        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ];
        $this->http = Http::withHeaders($headers);
    }

    /**
     * Send a text message via wa-api.cloud
     *
     * @param  string  $to  Recipient phone number (with country code, e.g., +1234567890)
     * @param  string  $message  Message text to send
     * @param  array  $options  Additional options (e.g., ['preview_url' => true])
     * @return array Response from API
     *
     * @throws Exception
     */
    public function sendMessage(string $to, string $message, array $options = []): array
    {
        try {
            // Validate phone number format
            $to = $this->formatPhoneNumber($to);
            if (empty($to)) {
                throw new Exception('Invalid phone number format');
            }

            if (empty($message)) {
                throw new Exception('Message text is required');
            }

            if (empty($this->accessToken)) {
                throw new Exception('WhatsApp Access Token is not configured');
            }

            $url = "{$this->baseUrl}/messages";

            $payload = [
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ];

            $response = $this->http->post($url, $payload);

            if (! $response->successful()) {
                $errorData = $response->json();
                Log::error('WhatsApp API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $errorData,
                    'phone_number' => $to,
                ]);

                $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? 'Unknown error occurred');
                throw new Exception("WhatsApp API request failed: {$errorMessage}", $response->status());
            }

            $data = $response->json();

            // Check if message actually failed despite successful HTTP response
            if (isset($data['has_errors']) && $data['has_errors'] === true) {
                $errors = $data['errors'] ?? [];
                $errorMessages = [];

                foreach ($errors as $error) {
                    $errorMessages[] = $error['message'] ?? ($error['title'] ?? 'Unknown error');
                    if (isset($error['error_data']['details'])) {
                        $errorMessages[] = $error['error_data']['details'];
                    }
                }

                $errorMessage = implode(' - ', $errorMessages);

                Log::error('WhatsApp message failed to send', [
                    'message_id' => $data['id'] ?? null,
                    'to' => $to,
                    'errors' => $errors,
                    'is_failed' => $data['is_failed'] ?? false,
                    'is_sent' => $data['is_sent'] ?? false,
                ]);

                throw new Exception("WhatsApp message failed: {$errorMessage}");
            }

            // Check if message was actually sent
            $isSent = $data['is_sent'] ?? true;
            $isFailed = $data['is_failed'] ?? false;

            if ($isFailed || ! $isSent) {
                $errorMessage = $data['message'] ?? 'Message failed to send';
                Log::warning('WhatsApp message may not have been sent', [
                    'message_id' => $data['id'] ?? null,
                    'to' => $to,
                    'is_sent' => $isSent,
                    'is_failed' => $isFailed,
                    'status' => $data['status'] ?? null,
                ]);

                throw new Exception("WhatsApp message failed: {$errorMessage}");
            }

            Log::info('WhatsApp message sent successfully', [
                'message_id' => $data['id'] ?? null,
                'to' => $to,
                'is_delivered' => $data['is_delivered'] ?? false,
                'is_sent' => $data['is_sent'] ?? false,
            ]);

            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'message_id' => $data['id'] ?? null,
                'status' => $data['status'] ?? 'sent',
                'is_sent' => $data['is_sent'] ?? false,
                'is_delivered' => $data['is_delivered'] ?? false,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message', [
                'to' => $to ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a template message with components (header, body, footer)
     *
     * @param  string  $to  Recipient phone number
     * @param  string  $templateName  Template name (e.g., 'new_year_2026')
     * @param  string  $languageCode  Language code (default: 'en')
     * @param  array  $components  Template components (header, body, footer)
     * @param  array  $options  Additional options
     * @return array Response from API
     *
     * @throws Exception
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'en', array $components = [], array $options = []): array
    {
        try {
            $to = $this->formatPhoneNumber($to);

            if (empty($to)) {
                throw new Exception('Invalid phone number format');
            }

            if (empty($templateName)) {
                throw new Exception('Template name is required');
            }

            if (empty($this->accessToken)) {
                throw new Exception('WhatsApp Access Token is not configured');
            }

            $url = "{$this->baseUrl}/messages";

            $template = [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                    'policy' => $options['policy'] ?? 'deterministic',
                ],
            ];

            // Add components if provided
            if (! empty($components)) {
                $template['components'] = $components;
            }

            $payload = [
                'to' => $to,
                'type' => 'template',
                'template' => $template,
            ];

            $response = $this->http->post($url, $payload);

            if (! $response->successful()) {
                $errorData = $response->json();
                Log::error('WhatsApp template API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $errorData,
                    'phone_number' => $to,
                    'template' => $templateName,
                ]);

                $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? 'Unknown error occurred');
                throw new Exception("WhatsApp template API request failed: {$errorMessage}", $response->status());
            }

            $data = $response->json();

            // Check if message actually failed despite successful HTTP response
            if (isset($data['has_errors']) && $data['has_errors'] === true) {
                $errors = $data['errors'] ?? [];
                $errorMessages = [];

                foreach ($errors as $error) {
                    $errorMessages[] = $error['message'] ?? ($error['title'] ?? 'Unknown error');
                    if (isset($error['error_data']['details'])) {
                        $errorMessages[] = $error['error_data']['details'];
                    }
                }

                $errorMessage = implode(' - ', $errorMessages);

                Log::error('WhatsApp template message failed to send', [
                    'message_id' => $data['id'] ?? null,
                    'to' => $to,
                    'template' => $templateName,
                    'errors' => $errors,
                    'is_failed' => $data['is_failed'] ?? false,
                    'is_sent' => $data['is_sent'] ?? false,
                    'has_errors' => $data['has_errors'] ?? false,
                ]);

                throw new Exception("WhatsApp template message failed: {$errorMessage}");
            }

            // Check if message was actually sent
            $isSent = $data['is_sent'] ?? true;
            $isFailed = $data['is_failed'] ?? false;

            if ($isFailed || ! $isSent) {
                $errorMessage = $data['message'] ?? 'Template message failed to send';
                Log::warning('WhatsApp template message may not have been sent', [
                    'message_id' => $data['id'] ?? null,
                    'to' => $to,
                    'template' => $templateName,
                    'is_sent' => $isSent,
                    'is_failed' => $isFailed,
                    'status' => $data['status'] ?? null,
                ]);

                throw new Exception("WhatsApp template message failed: {$errorMessage}");
            }

            Log::info('WhatsApp template message sent successfully', [
                'message_id' => $data['id'] ?? null,
                'to' => $to,
                'template' => $templateName,
                'is_delivered' => $data['is_delivered'] ?? false,
                'is_sent' => $data['is_sent'] ?? false,
                'has_errors' => $data['has_errors'] ?? false,
            ]);

            return [
                'success' => true,
                'message_id' => $data['id'] ?? null,
                'status' => $data['status'] ?? 'sent',
                'is_sent' => $data['is_sent'] ?? false,
                'is_delivered' => $data['is_delivered'] ?? false,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp template message', [
                'to' => $to ?? null,
                'template' => $templateName,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a template message with image header
     *
     * @param  string  $to  Recipient phone number
     * @param  string  $templateName  Template name
     * @param  string  $imageUrl  Image URL for header
     * @param  string  $languageCode  Language code
     * @param  string|null  $footerText  Optional footer text
     * @return array Response from API
     *
     * @throws Exception
     */
    public function sendTemplateWithImage(string $to, string $templateName, string $imageUrl, string $languageCode = 'en', ?string $footerText = null): array
    {
        $components = [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => $imageUrl,
                        ],
                    ],
                ],
            ],
            [
                'type' => 'body',
            ],
        ];

        if ($footerText) {
            $components[] = [
                'type' => 'footer',
                'text' => $footerText,
            ];
        }

        return $this->sendTemplate($to, $templateName, $languageCode, $components);
    }

    /**
     * Format phone number to WhatsApp format (remove all non-numeric characters except +)
     *
     *
     * @return string Formatted phone number
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        // If it doesn't start with +, ensure it's properly formatted
        if (! str_starts_with($cleaned, '+')) {
            // Remove leading zeros
            $cleaned = ltrim($cleaned, '0');
            // Add + if not present
            if (! str_starts_with($cleaned, '+')) {
                $cleaned = '+'.$cleaned;
            }
        }

        return $cleaned;
    }
}
