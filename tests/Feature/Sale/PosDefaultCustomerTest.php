<?php

use App\Http\Controllers\SaleController;
use Illuminate\Http\Request;
use Tests\Support\PosWorld;

/**
 * The POS preselects the walk-in "General Customer". That account is created
 * per tenant, so its id differs between tenants — the page must resolve the
 * current tenant's own record rather than assume a fixed id.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
});

/** @return array<string, mixed> */
function posPageProps(): array
{
    $request = Request::create('/sale/pos', 'GET', server: ['HTTP_X_INERTIA' => 'true']);
    app()->instance('request', $request);

    return app(SaleController::class)->posPage()->toResponse($request)->getData(true)['props'];
}

it('preselects the current tenant\'s general customer', function (): void {
    $generalCustomerId = $this->world->accounts['general_customer'];

    $props = posPageProps();

    expect($props['defaultCustomer'])->toMatchArray(['id' => $generalCustomerId, 'name' => 'General Customer'])
        ->and($props['saleData']['account_id'])->toBe($generalCustomerId)
        ->and(array_keys($props['customers']))->toBe([$generalCustomerId]);
});

it('defaults payment to the current tenant\'s cash account', function (): void {
    $props = posPageProps();

    expect($props['cashPaymentMethodId'])->toBe($this->world->accounts['cash'])
        ->and($props['cardPaymentMethodId'])->toBe($this->world->accounts['card'])
        ->and($props['saleData']['payment_method'])->toBe($this->world->accounts['cash']);
});
