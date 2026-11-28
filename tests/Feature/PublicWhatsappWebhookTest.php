<?php

use App\Models\Configuration;
use App\Models\Tenant;
use App\Services\TenantService;

beforeEach(function (): void {
    config([
        'services.whatsapp_webhook.verify_token' => 'env-token',
        'services.whatsapp_webhook.app_secret' => 'app-secret',
    ]);
});

function whatsappVerifyUrl(string $token, string $host = ''): string
{
    return $host.'/public-whatsapp/webhook?'.http_build_query([
        'hub.mode' => 'subscribe',
        'hub.verify_token' => $token,
        'hub.challenge' => '1158201444',
    ]);
}

it('echoes the challenge when the verify token matches', function (): void {
    $this->get(whatsappVerifyUrl('env-token'))
        ->assertOk()
        ->assertSeeText('1158201444', false);
});

it('rejects the handshake with a wrong verify token', function (): void {
    $this->get(whatsappVerifyUrl('wrong'))->assertForbidden();
});

it('prefers the tenant configuration verify token over the env one', function (): void {
    $tenant = Tenant::query()->create([
        'name' => 'Webhook Tenant',
        'code' => 'WHT',
        'subdomain' => 'wht-test',
        'is_active' => true,
    ]);
    app(TenantService::class)->setCurrentTenant($tenant);
    Configuration::query()->create(['key' => 'whatsapp_webhook_verify_token', 'value' => 'tenant-token']);

    $tenantHost = 'http://wht-test.localhost';

    $this->get(whatsappVerifyUrl('tenant-token', $tenantHost))->assertOk();
    $this->get(whatsappVerifyUrl('env-token', $tenantHost))->assertForbidden();
});

it('accepts an event signed with the app secret', function (): void {
    $payload = json_encode(['object' => 'whatsapp_business_account', 'entry' => []]);

    $this->call('POST', '/public-whatsapp/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $payload, 'app-secret'),
    ], $payload)->assertOk()->assertSeeText('EVENT_RECEIVED');
});

it('rejects an event with a bad signature', function (): void {
    $payload = json_encode(['object' => 'whatsapp_business_account', 'entry' => []]);

    $this->call('POST', '/public-whatsapp/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
    ], $payload)->assertForbidden();
});
