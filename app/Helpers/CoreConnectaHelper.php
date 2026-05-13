<?php

namespace App\Helpers;

use App\Models\Configuration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoreConnectaHelper
{
    private string $baseUrl;

    private string $apiKey;

    private ?int $defaultSessionId;

    private string $defaultSessionName;

    public function __construct()
    {
        $this->baseUrl = rtrim($this->connectaSetting('url', config('services.core_connecta.url', 'http://localhost:3000')), '/');
        $this->apiKey = $this->connectaSetting('api_key', config('services.core_connecta.api_key', ''));
        $sessionId = $this->connectaSetting('session_id', config('services.core_connecta.session_id'), false);
        $this->defaultSessionId = $sessionId ? (int) $sessionId : null;
        $this->defaultSessionName = $this->connectaSetting('session_name', config('services.core_connecta.session_name', 'Project Manager'));
    }

    public function getCall(string $endpoint): array
    {
        return match ($endpoint) {
            'check-status' => $this->checkStatus(),
            'get-qr' => $this->getQr(),
            default => $this->request('get', $endpoint),
        };
    }

    public function postCall(string $endpoint): array
    {
        return match ($endpoint) {
            'disconnect' => $this->disconnect(),
            default => $this->request('post', $endpoint),
        };
    }

    public function send(array $data): array
    {
        return $this->sendMessage(
            (string) ($data['number'] ?? $data['to'] ?? ''),
            (string) ($data['message'] ?? ''),
        );
    }

    public function sendMessage(string $to, string $message, array $options = []): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'success' => false,
                'message' => 'Message text is required.',
            ];
        }

        $sessionId = $this->outboundSessionId($options['session_id'] ?? null);
        if (! $sessionId) {
            return [
                'success' => false,
                'message' => 'No connected Core Connecta session found. Open Settings > WhatsApp and connect a session first.',
            ];
        }

        $conversationId = isset($options['conversation_id']) ? (int) $options['conversation_id'] : null;
        if ($conversationId > 0) {
            return $this->request('post', 'api/messages/send', [
                'session_id' => $sessionId,
                'conversation_id' => $conversationId,
                'message' => $message,
            ]);
        }

        $digitsTo = $this->digitsOnlyPhone($to);
        if ($digitsTo === '') {
            return [
                'success' => false,
                'message' => 'A valid recipient phone number is required (include country code, digits only or + prefix).',
            ];
        }

        return $this->request('post', 'api/messages/send', [
            'session_id' => $sessionId,
            'to' => $digitsTo,
            'message' => $message,
        ]);
    }

    public function sendImage(string $to, string $imageUrl, ?string $caption = null): array
    {
        return $this->sendMessage($to, $imageUrl);
    }

    public function sendTemplateWithImage(string $to, string $templateName, string $imageUrl, string $languageCode = 'en', ?string $footerText = null): array
    {
        return $this->sendImage($to, $imageUrl, $footerText);
    }

    public function checkStatus(): array
    {
        $sessions = $this->sessions();
        if (! $sessions['success']) {
            return $sessions;
        }

        $rows = $this->normalizedSessionRows($sessions);
        $connected = collect($rows)->firstWhere('status', 'connected');

        return [
            'success' => (bool) $connected,
            'ready' => (bool) $connected,
            'message' => $connected ? 'Core Connecta session is connected.' : 'Core Connecta tenant is reachable, but no WhatsApp session is connected.',
            'session' => $connected,
            'sessions' => $rows,
        ];
    }

    public function getQr(): array
    {
        return $this->getQrAfterStaleRecovery(false);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQrAfterStaleRecovery(bool $alreadyRetried): array
    {
        $sessionId = $this->sessionIdForQr();
        if (! $sessionId) {
            return [
                'success' => false,
                'message' => 'Could not create or find a Core Connecta WhatsApp session.',
            ];
        }

        $response = $this->request('get', "api/whatsapp/sessions/{$sessionId}/qr", ['format' => 'json']);

        if (($response['success'] ?? false) || $alreadyRetried || ! $this->isStaleSessionGatewayResponse($response)) {
            return $response;
        }

        $this->clearSessionId();

        return $this->getQrAfterStaleRecovery(true);
    }

    public function disconnect(): array
    {
        $sessionId = $this->defaultSessionId ?: $this->firstSessionId();
        if (! $sessionId) {
            $this->clearSessionId();

            return ['success' => true, 'message' => 'No Core Connecta session exists.', 'session_cleared' => true];
        }

        $response = $this->request('delete', "api/whatsapp/sessions/{$sessionId}");
        if ($response['success'] || ($response['status'] ?? null) === 404) {
            $this->clearSessionId();
            $response['success'] = true;
            $response['session_cleared'] = true;
            $response['message'] = $response['message'] ?? 'Core Connecta session disconnected.';
        }

        return $response;
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        if (! $this->baseUrl || ! $this->apiKey) {
            return [
                'success' => false,
                'message' => 'Core Connecta URL or API key is not configured.',
            ];
        }

        $endpoint = ltrim($endpoint, '/');
        $url = "{$this->baseUrl}/{$endpoint}";

        try {
            $request = $this->http();
            $response = in_array($method, ['get', 'delete'], true)
                ? $request->{$method}($url, $data)
                : $request->{$method}($url, $data);

            $payload = $response->json();
            if (! is_array($payload)) {
                $payload = ['message' => $response->body()];
            }

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $payload['error'] ?? $payload['message'] ?? "Core Connecta request failed with HTTP {$response->status()}.",
                    'status' => $response->status(),
                    'data' => $payload,
                ];
            }

            return array_merge(['success' => true], $payload, ['data' => $payload]);
        } catch (\Throwable $e) {
            Log::error('Core Connecta request failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => "Core Connecta request failed: {$e->getMessage()}",
            ];
        }
    }

    private function http(): PendingRequest
    {
        return Http::timeout(10)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $this->apiKey,
            ]);
    }

    private function sessions(): array
    {
        return $this->request('get', 'api/whatsapp/sessions');
    }

    private function connectedSessionId(): ?int
    {
        $sessions = $this->sessions();
        if (! $sessions['success']) {
            return null;
        }

        $rows = $this->normalizedSessionRows($sessions);
        $connected = collect($rows)->firstWhere('status', 'connected');

        return isset($connected['id']) ? (int) $connected['id'] : null;
    }

    private function firstSessionId(): ?int
    {
        $sessions = $this->sessions();
        if (! $sessions['success']) {
            return null;
        }

        $rows = $this->normalizedSessionRows($sessions);
        if ($rows === []) {
            return null;
        }

        return (int) $rows[0]['id'];
    }

    /**
     * Prefer the configured session when it is connected; otherwise any connected session.
     */
    private function outboundSessionId(?int $optionSessionId): ?int
    {
        if ($optionSessionId !== null && $optionSessionId > 0) {
            return $optionSessionId;
        }

        $sessions = $this->sessions();
        if (! ($sessions['success'] ?? false)) {
            return null;
        }

        $rows = $this->normalizedSessionRows($sessions);

        if ($this->defaultSessionId !== null && $this->defaultSessionId > 0) {
            foreach ($rows as $row) {
                if ((int) ($row['id'] ?? 0) === $this->defaultSessionId && ($row['status'] ?? '') === 'connected') {
                    return $this->defaultSessionId;
                }
            }
        }

        $connected = collect($rows)->firstWhere('status', 'connected');

        return isset($connected['id']) ? (int) $connected['id'] : null;
    }

    /**
     * Normalize list-session API payloads (bare array, or wrapped in `data` / `sessions`).
     *
     * @param  array<string, mixed>  $sessionsResponse
     * @return list<array<string, mixed>>
     */
    private function normalizedSessionRows(array $sessionsResponse): array
    {
        $data = $sessionsResponse['data'] ?? [];

        if (! is_array($data)) {
            return [];
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return $this->onlySessionShapeRows($data['data']);
        }

        if (isset($data['sessions']) && is_array($data['sessions'])) {
            return $this->onlySessionShapeRows($data['sessions']);
        }

        if (isset($data['id'], $data['status']) && ! array_is_list($data)) {
            return [$data];
        }

        if (array_is_list($data)) {
            return $this->onlySessionShapeRows($data);
        }

        $rows = [];
        foreach ($sessionsResponse as $key => $value) {
            if (is_int($key) && is_array($value) && isset($value['id'], $value['status'])) {
                $rows[] = $value;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function onlySessionShapeRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['id'], $row['status'])) {
                $out[] = $row;
            }
        }

        return $out;
    }

    private function digitsOnlyPhone(string $phoneNumber): string
    {
        return preg_replace('/\D+/', '', $phoneNumber) ?? '';
    }

    private function sessionIdForQr(): ?int
    {
        if ($this->defaultSessionId) {
            return $this->defaultSessionId;
        }

        $existing = $this->firstSessionId();
        if ($existing) {
            $this->saveSetting('core_connecta_session_id', (string) $existing);

            return $existing;
        }

        $created = $this->request('post', 'api/whatsapp/sessions/create', [
            'session_name' => $this->defaultSessionName,
        ]);

        if (! $created['success'] || empty($created['session_id'])) {
            return null;
        }

        $this->saveSetting('core_connecta_session_id', (string) $created['session_id']);

        return (int) $created['session_id'];
    }

    /**
     * Read Core Connecta settings from configuration, falling back to legacy `personal_whatsapp_gateway_*` keys.
     */
    private function connectaSetting(string $suffix, mixed $default, bool $fallbackWhenEmpty = true): mixed
    {
        foreach (['core_connecta_'.$suffix, 'personal_whatsapp_gateway_'.$suffix] as $key) {
            try {
                $configuration = Configuration::where('key', $key)->first();
            } catch (\Throwable) {
                $configuration = null;
            }

            if (! $configuration) {
                continue;
            }

            if ($configuration->value === '' && ! $fallbackWhenEmpty) {
                continue;
            }

            if ($configuration->value !== null && $configuration->value !== '') {
                return $configuration->value;
            }
        }

        return $default;
    }

    private function saveSetting(string $key, string $value): void
    {
        try {
            Configuration::updateOrCreate(['key' => $key], ['value' => $value]);
        } catch (\Throwable $e) {
            Log::debug('Could not persist Core Connecta setting', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function clearSessionId(): void
    {
        $this->defaultSessionId = null;
        $this->saveSetting('core_connecta_session_id', '');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function isStaleSessionGatewayResponse(array $response): bool
    {
        if (($response['status'] ?? null) === 404) {
            return true;
        }

        $message = strtolower((string) ($response['message'] ?? ''));

        return str_contains($message, 'session not found');
    }
}
