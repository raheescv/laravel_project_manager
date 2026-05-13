<?php

declare(strict_types=1);

use App\Helpers\CoreConnectaHelper;
use App\Models\Configuration;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Http;

it('recovers from a stale persisted session when refreshing the QR code', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Core Connecta Tenant',
        'code' => 'CCT',
        'subdomain' => 'cct-test',
        'is_active' => true,
    ]);

    app(TenantService::class)->setCurrentTenant($tenant);

    foreach (
        [
            ['core_connecta_url', 'http://gateway.test'],
            ['core_connecta_api_key', 'secret-key'],
            ['core_connecta_session_id', '999'],
            ['core_connecta_session_name', 'Test Session'],
        ] as [$key, $value]
    ) {
        Configuration::query()->create([
            'tenant_id' => $tenant->id,
            'key' => $key,
            'value' => $value,
        ]);
    }

    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $url = $request->url();

        if (str_contains($url, '/api/whatsapp/sessions/999/qr')) {
            return Http::response(['message' => 'Session not found'], 404);
        }

        if (rtrim($url, '?') === 'http://gateway.test/api/whatsapp/sessions' && $request->method() === 'GET') {
            return Http::response([], 200);
        }

        if (str_contains($url, '/api/whatsapp/sessions/create') && $request->method() === 'POST') {
            return Http::response(['session_id' => 77], 200);
        }

        if (str_contains($url, '/api/whatsapp/sessions/77/qr')) {
            return Http::response(['qr' => 'qr-data', 'ready' => false, 'message' => 'Scan me'], 200);
        }

        return Http::response(['message' => 'Unexpected URL: '.$url], 500);
    });

    $helper = new CoreConnectaHelper();
    $result = $helper->getQr();

    expect($result['success'] ?? false)->toBeTrue()
        ->and($result['qr'] ?? null)->toBe('qr-data');

    $storedId = Configuration::query()
        ->where('key', 'core_connecta_session_id')
        ->value('value');

    expect($storedId)->toBe('77');
});

it('posts outbound messages with digits-only to and session id', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Core Connecta Send Tenant',
        'code' => 'CCS',
        'subdomain' => 'ccs-test',
        'is_active' => true,
    ]);

    app(TenantService::class)->setCurrentTenant($tenant);

    foreach (
        [
            ['core_connecta_url', 'http://gateway-send.test'],
            ['core_connecta_api_key', 'send-secret'],
            ['core_connecta_session_id', '5'],
        ] as [$key, $value]
    ) {
        Configuration::query()->create([
            'tenant_id' => $tenant->id,
            'key' => $key,
            'value' => $value,
        ]);
    }

    Http::fake([
        'http://gateway-send.test/api/whatsapp/sessions' => Http::response([
            ['id' => 5, 'session_name' => 'Main', 'phone_number' => '966500000000', 'status' => 'connected', 'created_at' => '2026-01-01'],
        ], 200),
        'http://gateway-send.test/api/messages/send' => Http::response(['success' => true, 'message' => ['id' => 99]], 200),
    ]);

    $helper = new CoreConnectaHelper();
    $result = $helper->sendMessage('+966 501 234 567', 'Hello from test');

    expect($result['success'] ?? false)->toBeTrue();

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        if (! str_contains($request->url(), '/api/messages/send')) {
            return false;
        }

        $data = $request->data();

        return ($data['session_id'] ?? null) === 5
            && ($data['to'] ?? null) === '966501234567'
            && ($data['message'] ?? null) === 'Hello from test';
    });
});
