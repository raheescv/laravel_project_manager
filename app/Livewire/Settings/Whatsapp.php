<?php

namespace App\Livewire\Settings;

use App\Helpers\Facades\WhatsappHelper;
use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Whatsapp extends Component
{
    public string $driver = 'meta';

    public ?string $gatewayUrl = null;

    public ?string $gatewayApiKey = null;

    public ?string $gatewaySessionId = null;

    public ?string $gatewaySessionName = null;

    public ?string $metaAccessToken = null;

    public ?string $metaBaseUrl = null;

    public ?string $metaTemplateName = null;

    public ?string $whatsappQr = null;

    public bool $isConnected = false;

    public ?string $statusMessage = null;

    public ?string $number = null;

    public ?string $message = null;

    /**
     * Resolved gateway base URL and host label for Gateway notes.
     *
     * @return array{has_url: bool, href: string, label: string}
     */
    #[Computed]
    public function gatewayPublicInfo(): array
    {
        $raw = trim((string) ($this->gatewayUrl ?: config('services.core_connecta.url', '')));
        if ($raw === '') {
            return ['has_url' => false, 'href' => '', 'label' => ''];
        }

        $href = rtrim($raw, '/');
        $parts = parse_url($href);
        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        if ($scheme === '' || $host === '') {
            return ['has_url' => false, 'href' => '', 'label' => ''];
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $label = $host.$port;

        return ['has_url' => true, 'href' => $href, 'label' => $label];
    }

    public function mount(): void
    {
        $this->driver = $this->setting('whatsapp_driver', config('services.whatsapp.driver', 'meta'));
        if ($this->driver === 'personal_gateway') {
            $this->driver = 'core_connecta';
            $this->saveSetting('whatsapp_driver', 'core_connecta');
        }

        $this->gatewayUrl = $this->connectaSetting('url', config('services.core_connecta.url'));
        $this->gatewayApiKey = $this->connectaSetting('api_key', config('services.core_connecta.api_key'));
        $this->gatewaySessionId = $this->connectaSetting('session_id', config('services.core_connecta.session_id'), false);
        $this->gatewaySessionName = $this->connectaSetting('session_name', config('services.core_connecta.session_name', config('app.name')));
        $this->metaAccessToken = $this->setting('meta_whatsapp_access_token', config('services.meta_whatsapp.access_token'));
        $this->metaBaseUrl = $this->setting('meta_whatsapp_base_url', config('services.meta_whatsapp.base_url'));
        $this->metaTemplateName = $this->setting('meta_whatsapp_template_name', config('services.meta_whatsapp.template_name', 'invoice_slip'));
        $this->number = '';
        $this->message = 'This is a sample message from '.config('app.name');

        $this->checkClientStatus();
    }

    public function saveIntegration(): void
    {
        abort_unless(auth()->user()?->can('whatsapp.integration'), 403);
        $this->validate([
            'driver' => 'required|in:meta,localhost,core_connecta,personal_gateway',
            'gatewayUrl' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->usesCoreConnectaDriver()),
                'url',
                'max:255',
            ],
            'gatewayApiKey' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->usesCoreConnectaDriver()),
                'string',
                'max:255',
            ],
            'gatewaySessionId' => 'nullable|integer|min:1',
            'gatewaySessionName' => 'nullable|string|max:100',
            'metaAccessToken' => 'nullable|string|max:1000',
            'metaBaseUrl' => 'nullable|url|max:255',
            'metaTemplateName' => 'nullable|string|max:100',
        ]);

        if ($this->driver === 'personal_gateway') {
            $this->driver = 'core_connecta';
        }

        $this->saveSetting('whatsapp_driver', $this->driver);
        $this->saveSetting('core_connecta_url', rtrim((string) $this->gatewayUrl, '/'));
        $this->saveSetting('core_connecta_api_key', (string) $this->gatewayApiKey);
        $this->saveSetting('core_connecta_session_id', (string) $this->gatewaySessionId);
        $this->saveSetting('core_connecta_session_name', (string) $this->gatewaySessionName);
        $this->saveSetting('meta_whatsapp_access_token', (string) $this->metaAccessToken);
        $this->saveSetting('meta_whatsapp_base_url', rtrim((string) $this->metaBaseUrl, '/'));
        $this->saveSetting('meta_whatsapp_template_name', (string) $this->metaTemplateName);

        writeToEnv('WHATSAPP_DRIVER', $this->driver);
        writeToEnv('CORE_CONNECTA_URL', rtrim((string) $this->gatewayUrl, '/'));
        writeToEnv('CORE_CONNECTA_API_KEY', (string) $this->gatewayApiKey);
        writeToEnv('CORE_CONNECTA_SESSION_ID', (string) $this->gatewaySessionId);
        writeToEnv('CORE_CONNECTA_SESSION_NAME', $this->envString((string) $this->gatewaySessionName));
        writeToEnv('META_WHATSAPP_ACCESS_TOKEN', (string) $this->metaAccessToken);
        writeToEnv('META_WHATSAPP_BASE_URL', rtrim((string) $this->metaBaseUrl, '/'));
        writeToEnv('META_WHATSAPP_TEMPLATE_NAME', (string) $this->metaTemplateName);

        Artisan::call('optimize:clear');
        $this->dispatch('success', ['message' => 'WhatsApp integration settings saved.']);
        $this->checkClientStatus();
    }

    public function checkClientStatus(): void
    {
        $this->whatsappQr = null;

        if ($this->driver === 'meta') {
            $this->isConnected = filled($this->metaAccessToken);
            $this->statusMessage = $this->isConnected
                ? 'Meta WhatsApp credentials are configured. Use Send Test Message to verify delivery.'
                : 'Meta WhatsApp access token is not configured.';

            return;
        }

        $response = WhatsappHelper::getCall('check-status');
        $this->isConnected = (bool) ($response['success'] ?? false);
        $this->statusMessage = $response['message'] ?? ($this->isConnected ? 'WhatsApp connected.' : 'WhatsApp is not connected.');

        if (! $this->isConnected) {
            $this->getWhatsappQr();
        }
    }

    public function getWhatsappQr(): void
    {
        abort_unless(auth()->user()?->can('whatsapp.integration'), 403);
        if ($this->driver === 'meta') {
            $this->dispatch('error', ['message' => 'QR pairing is only available for Core Connecta and the legacy localhost driver.']);

            return;
        }

        $response = WhatsappHelper::getCall('get-qr');
        if (! ($response['success'] ?? false)) {
            $this->whatsappQr = null;
            $this->dispatch('error', ['message' => $response['message'] ?? 'QR not returned.']);

            return;
        }

        $this->whatsappQr = $response['qr'] ?? null;
        $this->isConnected = (bool) ($response['ready'] ?? ($response['status'] ?? null) === 'connected');
        $this->statusMessage = $response['message'] ?? ($this->whatsappQr ? 'Scan the QR code to connect.' : 'Session is starting. Refresh QR again shortly.');
    }

    public function disconnect(): void
    {
        abort_unless(auth()->user()?->can('whatsapp.integration'), 403);
        if ($this->driver === 'meta') {
            $this->dispatch('error', ['message' => 'Meta WhatsApp sessions are managed in Meta Business Manager.']);

            return;
        }

        $response = WhatsappHelper::postCall('disconnect');
        $this->isConnected = false;
        $this->whatsappQr = null;
        $this->statusMessage = $response['message'] ?? 'Disconnected.';

        if (($response['success'] ?? false) || ($response['session_cleared'] ?? false)) {
            $this->clearGatewaySessionId();
        }

        $this->dispatch(($response['success'] ?? false) ? 'success' : 'error', ['message' => $this->statusMessage]);
    }

    public function sendSampleSms(): void
    {
        abort_unless(auth()->user()?->can('whatsapp.integration'), 403);
        $this->validate([
            'number' => 'required|string|max:32',
            'message' => 'required|string|max:1000',
        ]);

        try {
            $response = $this->driver === 'meta'
                ? WhatsappHelper::sendMessage($this->number, $this->message)
                : WhatsappHelper::send([
                    'number' => $this->number,
                    'message' => $this->message,
                    'filePath' => public_path('node/sample.pdf'),
                ]);
        } catch (\Throwable $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        $this->dispatch(($response['success'] ?? false) ? 'success' : 'error', [
            'message' => $response['message'] ?? (($response['success'] ?? false) ? 'Message sent.' : 'Message failed.'),
        ]);
    }

    public function usesCoreConnectaDriver(): bool
    {
        return in_array($this->driver, ['core_connecta', 'personal_gateway'], true);
    }

    private function connectaSetting(string $suffix, mixed $default, bool $fallbackWhenEmpty = true): mixed
    {
        foreach (['core_connecta_'.$suffix, 'personal_whatsapp_gateway_'.$suffix] as $key) {
            $configuration = Configuration::where('key', $key)->first();

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

    private function setting(string $key, mixed $default = null, bool $fallbackWhenEmpty = true): mixed
    {
        $configuration = Configuration::where('key', $key)->first();

        if (! $configuration) {
            return $default;
        }

        if ($configuration->value === '' && ! $fallbackWhenEmpty) {
            return null;
        }

        return $configuration->value !== null && $configuration->value !== '' ? $configuration->value : $default;
    }

    private function saveSetting(string $key, string $value): void
    {
        Configuration::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private function envString(string $value): string
    {
        return '"'.str_replace('"', '\"', $value).'"';
    }

    private function clearGatewaySessionId(): void
    {
        $this->gatewaySessionId = null;
        $this->saveSetting('core_connecta_session_id', '');
        writeToEnv('CORE_CONNECTA_SESSION_ID', '');
        Artisan::call('optimize:clear');
    }

    public function render()
    {
        return view('livewire.settings.whatsapp');
    }
}
