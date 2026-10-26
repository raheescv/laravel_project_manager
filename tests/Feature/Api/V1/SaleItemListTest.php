<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * The Items tab of the mobile Sales list: every sold line on its own row, under
 * the same filters and the same employee self-scope as the invoice list.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100, price: 50);
    Sanctum::actingAs($this->world->user);
});

function ringUp(PosWorld $world, int $quantity): void
{
    $total = $quantity * (float) $world->product->mrp;
    test()->postJson($world->url('/api/v1/sale'), $world->salePayload([
        'items' => [[
            'productId' => $world->product->id,
            'quantity' => $quantity,
            'unitPrice' => (float) $world->product->mrp,
            'discount' => 0,
        ]],
        'totalPayment' => $total,
    ]))->assertSuccessful();
}

it('lists the same product on two invoices as two rows, not one grouped row', function (): void {
    ringUp($this->world, 2);
    ringUp($this->world, 3);

    $response = $this->getJson($this->world->url('/api/v1/sale/items'))->assertSuccessful();

    expect($response->json('data.data'))->toHaveCount(2)
        ->and(collect($response->json('data.data'))->pluck('product.id')->unique()->all())->toBe([(string) $this->world->product->id])
        ->and($response->json('data.summary.lines'))->toBe(2)
        ->and((float) $response->json('data.summary.quantity'))->toBe(5.0)
        ->and((float) $response->json('data.summary.total'))->toBe(250.0)
        ->and($response->json('data.data.0'))->toHaveKeys(['sale_id', 'invoice_no', 'customer', 'employee', 'quantity', 'unit_price', 'discount', 'total']);
});

it('searches lines by product name', function (): void {
    ringUp($this->world, 1);

    $this->getJson($this->world->url('/api/v1/sale/items?search='.urlencode($this->world->product->name)))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.lines', 1);

    $this->getJson($this->world->url('/api/v1/sale/items?search=no-such-product'))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.lines', 0);
});

it('applies the invoice filters to the parent sale', function (): void {
    ringUp($this->world, 1);

    $this->getJson($this->world->url('/api/v1/sale/items?status=draft'))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.lines', 0);

    $this->getJson($this->world->url('/api/v1/sale/items?status=completed'))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.lines', 1);
});

it('shows a non-admin employee only the lines of their own sales', function (): void {
    ringUp($this->world, 1);

    $employee = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'type' => 'employee',
        'is_admin' => 0,
    ]);
    Sanctum::actingAs($employee);

    $this->getJson($this->world->url('/api/v1/sale/items'))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.lines', 0);
});

it('reports the outstanding balance on the invoice list summary', function (): void {
    ringUp($this->world, 1);

    $this->getJson($this->world->url('/api/v1/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.invoices', 1)
        ->assertJsonPath('data.summary.total_balance', 0);
});

it('breaks same-date ties by id in the chosen date direction', function (): void {
    ringUp($this->world, 1);
    ringUp($this->world, 2);
    ringUp($this->world, 3);

    $oldest = $this->getJson($this->world->url('/api/v1/sale?sort_by=date&sort_direction=asc'))->assertSuccessful();
    $newest = $this->getJson($this->world->url('/api/v1/sale?sort_by=date&sort_direction=desc'))->assertSuccessful();
    $oldestIds = collect($oldest->json('data.data'))->pluck('id')->map(fn ($id): int => (int) $id)->all();
    $newestIds = collect($newest->json('data.data'))->pluck('id')->map(fn ($id): int => (int) $id)->all();

    $oldestLines = $this->getJson($this->world->url('/api/v1/sale/items?sort_by=date&sort_direction=asc'))->assertSuccessful();
    $oldestQuantities = collect($oldestLines->json('data.data'))->pluck('quantity')->map(fn ($quantity): float => (float) $quantity)->all();

    expect($oldestIds)->toHaveCount(3)
        ->and($oldestIds)->toBe(collect($oldestIds)->sort()->values()->all())
        ->and($newestIds)->toBe(array_reverse($oldestIds))
        ->and($oldestQuantities)->toBe([1.0, 2.0, 3.0]);
});
