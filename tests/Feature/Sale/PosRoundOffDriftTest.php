<?php

use App\Models\Configuration;
use App\Models\Sale;
use Tests\Support\PosWorld;

/**
 * Sale 4318: 1.23 g at 1,050 with 3% tax. JS float math made the POS round the
 * tax to 38.74 (round off -0.24, grand total 1,300) while the server saved
 * 38.75 and kept the client's -0.24, storing a grand total of 1,300.01.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function driftedPosPayload(PosWorld $world, array $overrides = []): array
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
        'other_discount' => 30,
        'round_off' => -0.24,
        'gross_amount' => 1291.5,
        'item_discount' => 0,
        'tax_amount' => 38.74,
        'total' => 1330.24,
        'grand_total' => 1300,
        'items' => [[
            'inventory_id' => $inventoryId,
            'product_id' => $world->product->id,
            'unit_id' => $world->product->unit_id,
            'employee_id' => $world->user->id,
            'unit_price' => 1050,
            'quantity' => 1.23,
            'conversion_factor' => 1,
            'discount' => 0,
            'tax' => 3,
            'tax_amount' => 38.75,
        ]],
        'comboOffers' => [],
        'payment_method' => 'credit',
        'custom_payment_data' => ['payments' => []],
        'send_to_whatsapp' => false,
        'notes' => null,
        'status' => 'completed',
    ], $overrides);
}

it('recalculates the round off when the saved item totals differ from the client', function (): void {
    $response = $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), driftedPosPayload($this->world))
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('sale_id'));

    expect((float) $sale->tax_amount)->toBe(38.75)
        ->and((float) $sale->total)->toBe(1330.25)
        ->and((float) $sale->round_off)->toBe(-0.25)
        ->and((float) $sale->grand_total)->toBe(1300.0);
});

it('leaves the round off alone when round off is disabled', function (): void {
    Configuration::updateOrCreate(['key' => 'round_off_enabled'], ['value' => 'no']);

    $response = $this->actingAs($this->world->user)
        ->withSession(['branch_id' => $this->world->branch->id])
        ->postJson($this->world->url('/pos/submit'), driftedPosPayload($this->world, ['round_off' => 0, 'grand_total' => 1300.24]))
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('sale_id'));

    expect((float) $sale->round_off)->toBe(0.0)
        ->and((float) $sale->grand_total)->toBe(1300.25);
});
