<?php

namespace App\Helpers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalHostWhatsappHelper
{
    private string $baseUrl;

    private const TIMEOUT = 3; // Reduced timeout

    private const MAX_RETRIES = 2;

    private const RETRY_DELAY = 100; // milliseconds

    private const DEFAULT_PORT = 3004;

    public function __construct()
    {
        $port = config('constants.whatsapp_port', self::DEFAULT_PORT);
        $baseUrl = config('constants.whatsapp_server_url', 'http://localhost:'.$port);
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    private function isPortOpen(): bool
    {
        $parts = parse_url($this->baseUrl);
        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? self::DEFAULT_PORT;

        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
        if (is_resource($connection)) {
            fclose($connection);

            return true;
        }

        return false;
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        if (! $this->isPortOpen()) {
            Log::error('WhatsApp server port not accessible', [
                'url' => $this->baseUrl,
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'message' => "WhatsApp server not running on {$this->baseUrl}. Please start the WhatsApp server using 'node server.js'",
            ];
        }

        $attempt = 1;
        $lastError = null;

        do {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->retry(self::MAX_RETRIES, self::RETRY_DELAY)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->$method("{$this->baseUrl}/{$endpoint}", $data);

                if (! $response->successful()) {
                    Log::warning('WhatsApp server returned error', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                        'endpoint' => $endpoint,
                    ]);
                }

                return $response->json() ?: [
                    'success' => false,
                    'message' => 'WhatsApp server responded but returned invalid data',
                ];

            } catch (RequestException $e) {
                $lastError = $e;
                Log::warning('WhatsApp request failed', [
                    'message' => $e->getMessage(),
                    'endpoint' => $endpoint,
                    'attempt' => $attempt,
                ]);

                usleep(self::RETRY_DELAY * 1000);
                $attempt++;
            }
        } while ($attempt <= self::MAX_RETRIES);

        return [
            'success' => false,
            'message' => "WhatsApp server error: {$lastError?->getMessage()}",
        ];
    }

    public function getCall(string $endpoint): array
    {
        return $this->makeRequest('get', $endpoint);
    }

    public function postCall(string $endpoint): array
    {
        return $this->makeRequest('post', $endpoint);
    }

    public function send(array $data): array
    {
        return $this->makeRequest('post', 'send-message', $data);
    }
}
