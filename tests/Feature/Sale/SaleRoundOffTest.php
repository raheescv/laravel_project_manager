<?php

use App\Http\Controllers\SaleController;
use App\Livewire\Settings\SaleConfiguration;
use App\Models\Configuration;
use App\Models\Sale;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Sale Configuration → Round Off. On by default: the grand total is
 * rounded to the nearest whole number on the web POS and the mobile app, with
 * the difference kept in `sales.round_off`.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(price: 49.6);
});

/** @return array<string, mixed> */
function roundOffPosPageProps(): array
{
    $request = Request::create('/sale/pos', 'GET', server: ['HTTP_X_INERTIA' => 'true']);
    app()->instance('request', $request);

    return app(SaleController::class)->posPage()->toResponse($request)->getData(true)['props'];
}

it('is enabled when the setting has never been saved', function (): void {
    expect(saleRoundOffEnabled())->toBeTrue();

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.round_off_enabled', true);

    $this->actingAs($this->world->user);
    expect(roundOffPosPageProps()['roundOffEnabled'])->toBeTrue();
});

it('follows the setting when it is switched off', function (): void {
    Configuration::updateOrCreate(['key' => 'round_off_enabled'], ['value' => 'no']);

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.round_off_enabled', false);

    $this->actingAs($this->world->user);
    expect(roundOffPosPageProps()['roundOffEnabled'])->toBeFalse();
});

it('is saved from the Sale Configuration settings tab', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(SaleConfiguration::class)
        ->assertSet('round_off_enabled', 'yes')
        ->set('round_off_enabled', 'no')
        ->call('save');

    expect(Configuration::where('key', 'round_off_enabled')->value('value'))->toBe('no');
});

it('stores the round off the mobile app sends, so the grand total is whole', function (): void {
    Sanctum::actingAs($this->world->user);

    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'roundOff' => 0.4,
        'totalPayment' => 50,
    ]))->assertSuccessful()
        ->assertJsonPath('data.summary.round_off', 0.4);

    $sale = Sale::withoutGlobalScopes()->find($response->json('data.id'));

    expect((float) $sale->round_off)->toBe(0.4)
        ->and((float) $sale->grand_total)->toBe(50.0)
        ->and((float) $sale->balance)->toBe(0.0);
});

it('keeps round off at zero for an app that does not send one', function (): void {
    Sanctum::actingAs($this->world->user);

    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('data.id'));

    expect((float) $sale->round_off)->toBe(0.0)
        ->and((float) $sale->grand_total)->toBe(49.6);
});

it('rejects a round off of more than half a unit', function (): void {
    Sanctum::actingAs($this->world->user);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['roundOff' => 0.6]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('roundOff');
});
