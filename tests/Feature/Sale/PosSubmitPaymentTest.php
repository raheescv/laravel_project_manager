<?php

use App\Models\Sale;
use App\Models\SalePayment;
use Tests\Support\PosWorld;

/**
 * The Vue POS confirmation modal posts the chosen tender as `payment_method`
 * (an account id, 'credit' or 'custom') to /pos/submit, which turns it into the
 * sale's payment rows.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
});

/** The body the Vue POS posts to /pos/submit for one item at 50. */
function posSubmitPayload(PosWorld $world, array $overrides = []): array
{
    $inventoryId = DB::table('inventories')
        ->where('product_id', $world->product->id)
        ->where('branch_id', $world->branch->id)
        ->value('id');

    return array_merge([
        'id' => null,
        'date' => today()->toDateString(),
        'employee_id' => $world->user->id,
        'sale_type' => 'normal',
        'account_id' => $world->accounts['general_customer'],
        'customer_mobile' => null,
        'other_discount' => 0,
        'round_off' => 0,
        'gross_amount' => 50,
        'item_discount' => 0,
        'tax_amount' => 0,
        'total' => 50,
        'grand_total' => 50,
        'items' => [[
            'inventory_id' => $inventoryId,
            'product_id' => $world->product->id,
            'unit_id' => $world->product->unit_id,
            'employee_id' => $world->user->id,
            'unit_price' => 50,
            'quantity' => 1,
            'conversion_factor' => 1,
            'discount' => 0,
            'tax' => 0,
        ]],
        'comboOffers' => [],
        'custom_payment_data' => ['payments' => []],
        'send_to_whatsapp' => false,
        'notes' => null,
        'status' => 'completed',
    ], $overrides);
}

it('saves the tender picked in the confirmation modal as the sale payment', function (string $slug): void {
    $methodId = $this->world->accounts[$slug];

    $response = $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), posSubmitPayload($this->world, ['payment_method' => $methodId]))
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('sale_id'));
    $payments = SalePayment::withoutGlobalScopes()->where('sale_id', $sale->id)->get();

    expect($payments)->toHaveCount(1)
        ->and($payments->first()->payment_method_id)->toBe($methodId)
        ->and((float) $payments->first()->amount)->toBe(50.0)
        ->and((float) $sale->paid)->toBe(50.0);
})->with(['cash', 'card']);

it('refuses a completed sale whose payment method is missing or unknown', function (mixed $paymentMethod): void {
    $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), posSubmitPayload($this->world, ['payment_method' => $paymentMethod]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payment_method');

    expect(Sale::withoutGlobalScopes()->where('tenant_id', $this->world->tenant->id)->count())->toBe(0);
})->with([
    'null (a stale Card id)' => [null],
    'an account that is not a payment method' => fn () => $this->world->accounts['general_customer'],
    'an id that does not exist' => [999999],
]);

it('saves an explicit credit sale with no payment', function (): void {
    $response = $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), posSubmitPayload($this->world, ['payment_method' => 'credit']))
        ->assertSuccessful();

    expect(SalePayment::withoutGlobalScopes()->where('sale_id', $response->json('sale_id'))->count())->toBe(0);
});

it('refuses a custom tender with no payments or an unknown method', function (array $payments): void {
    $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), posSubmitPayload($this->world, [
            'payment_method' => 'custom',
            'custom_payment_data' => ['payments' => $payments],
        ]))
        ->assertUnprocessable();

    expect(Sale::withoutGlobalScopes()->where('tenant_id', $this->world->tenant->id)->count())->toBe(0);
})->with([
    'no payments' => [[]],
    'unknown method' => [[['payment_method_id' => 999999, 'amount' => 50]]],
]);

it('saves a custom split across cash and card', function (): void {
    $response = $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), posSubmitPayload($this->world, [
            'payment_method' => 'custom',
            'custom_payment_data' => ['payments' => [
                ['payment_method_id' => $this->world->accounts['cash'], 'amount' => 20],
                ['payment_method_id' => $this->world->accounts['card'], 'amount' => 30],
            ]],
        ]))
        ->assertSuccessful();

    expect(SalePayment::withoutGlobalScopes()->where('sale_id', $response->json('sale_id'))->pluck('amount', 'payment_method_id')->map(fn ($amount) => (float) $amount)->all())
        ->toBe([$this->world->accounts['cash'] => 20.0, $this->world->accounts['card'] => 30.0]);
});

it('still saves a draft without a payment method', function (): void {
    $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), posSubmitPayload($this->world, ['payment_method' => null, 'status' => 'draft']))
        ->assertSuccessful();
});
