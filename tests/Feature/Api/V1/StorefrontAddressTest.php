<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\PosWorld;

/**
 * Qatar National Address lookups behind the storefront delivery form. QNAS is
 * faked at the HTTP layer; what matters is that the token never leaves the
 * server, answers are reshaped and cached, and a failure is neither cached nor
 * fatal (503, so the form falls back to typed numbers).
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    config(['services.qnas.token' => 'qnas-token', 'services.qnas.domain' => 'shop.example']);
    Cache::forget('qnas:zones');
    Cache::forget('qnas:streets:56');
    Cache::forget('qnas:buildings:56:340');
});

it('merges districts that share a zone and sends the token from the server', function (): void {
    Http::fake(['qnas.qa/get_zones' => Http::response([
        ['zone_number' => 5, 'zone_name_en' => 'Fereej Al Asmakh', 'zone_name_ar' => 'فريج الاصمخ'],
        ['zone_number' => 2, 'zone_name_en' => 'Al Bidda', 'zone_name_ar' => 'البدع'],
        ['zone_number' => 5, 'zone_name_en' => 'Al Najada', 'zone_name_ar' => 'النجادة'],
    ])]);

    $this->getJson($this->world->url('/api/v1/storefront/address/zones'))
        ->assertOk()
        ->assertJsonPath('data', [
            ['number' => 2, 'name_en' => 'Al Bidda', 'name_ar' => 'البدع'],
            ['number' => 5, 'name_en' => 'Fereej Al Asmakh · Al Najada', 'name_ar' => 'فريج الاصمخ · النجادة'],
        ]);

    Http::assertSent(fn (Request $request) => $request->hasHeader('X-Token', 'qnas-token')
        && $request->hasHeader('X-Domain', 'shop.example'));
});

it('caches lists so QNAS is asked once', function (): void {
    Http::fake(['qnas.qa/get_streets/56' => Http::response([
        ['street_number' => 340, 'street_name_en' => 'Al Wakra Road', 'street_name_ar' => 'طريق الوكرة'],
        ['street_number' => 61, 'street_name_en' => '0', 'street_name_ar' => '0'],
    ])]);

    foreach (range(1, 3) as $ignored) {
        $this->getJson($this->world->url('/api/v1/storefront/address/zones/56/streets'))
            ->assertOk()
            ->assertJsonPath('data.0', ['number' => 61, 'name_en' => null, 'name_ar' => null])
            ->assertJsonPath('data.1.name_en', 'Al Wakra Road');
    }

    Http::assertSentCount(1);
});

it('returns buildings with their position', function (): void {
    Http::fake(['qnas.qa/get_buildings/56/340' => Http::response([
        ['zone_number' => 56, 'street_number' => 340, 'building_number' => '12', 'x' => '25.2854473', 'y' => '51.5310398'],
        ['zone_number' => 56, 'street_number' => 340, 'building_number' => '6', 'x' => '25.28', 'y' => '51.53'],
    ])]);

    $this->getJson($this->world->url('/api/v1/storefront/address/zones/56/streets/340/buildings'))
        ->assertOk()
        ->assertJsonPath('data.0', ['number' => '6', 'lat' => 25.28, 'lng' => 51.53])
        ->assertJsonPath('data.1', ['number' => '12', 'lat' => 25.2854473, 'lng' => 51.5310398]);
});

it('answers 503 without caching when QNAS refuses', function (): void {
    Http::fake(['qnas.qa/*' => Http::sequence()
        ->push(['status' => 'error', 'message' => 'Invalid token or domain.'], 401)
        ->push([['zone_number' => 1, 'zone_name_en' => 'Al Jasra', 'zone_name_ar' => 'الجسرة']])]);

    $this->getJson($this->world->url('/api/v1/storefront/address/zones'))->assertStatus(503);
    $this->getJson($this->world->url('/api/v1/storefront/address/zones'))->assertOk()->assertJsonPath('data.0.number', 1);
});

it('is off until a token is configured', function (): void {
    config(['services.qnas.token' => null]);
    Http::fake();

    $this->getJson($this->world->url('/api/v1/storefront/address/zones'))->assertStatus(503);

    Http::assertNothingSent();
});
