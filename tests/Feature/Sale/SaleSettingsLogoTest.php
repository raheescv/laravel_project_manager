<?php

use App\Models\Configuration;
use App\Support\TenantCache;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * GET /settings/sale carries the company logo so the mobile catalog can paint
 * it on products without a photo, as the web POS does.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    TenantCache::forget('logo');
});

it('returns the configured company logo', function (): void {
    Configuration::updateOrCreate(['key' => 'logo'], ['value' => 'storage/company_image/acme.png']);
    TenantCache::forget('logo');

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.logo_url', asset('storage/company_image/acme.png'));
});

it('falls back to the bundled logo when none is configured', function (): void {
    Configuration::where('key', 'logo')->delete();
    TenantCache::forget('logo');

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.logo_url', asset('assets/img/logo.svg'));
});
