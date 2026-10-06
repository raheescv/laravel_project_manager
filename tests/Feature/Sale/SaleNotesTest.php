<?php

use App\Http\Middleware\RequireOpenDaySession;
use App\Models\Sale;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * `sales.notes` is the free-text note a cashier attaches at checkout — the web
 * POS confirm modal and the mobile Review & Pay screen. It has to be stored,
 * handed back when the sale is re-opened, and survive edits from an app build
 * that predates the field.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);

    $this->editPayload = fn (Sale $sale, array $overrides = []) => array_merge([
        'customerName' => 'Walk-in Customer',
        'items' => $sale->items->map(fn ($item) => [
            'id' => (int) $item->id,
            'productId' => (int) $item->product_id,
            'quantity' => (float) $item->quantity,
            'unitPrice' => (float) $item->unit_price,
            'discount' => (float) $item->discount,
        ])->all(),
        'discount' => 0,
        'tip' => 0,
        'paymentMethod' => 'Cash',
        'totalPayment' => (float) $sale->grand_total,
        'status' => 'draft',
    ], $overrides);

    $this->parkDraft = function (array $overrides = []): Sale {
        $this->postJson(
            $this->world->url('/api/v1/sale'),
            $this->world->salePayload(array_merge(['status' => 'draft'], $overrides)),
        )->assertSuccessful();

        return Sale::withoutGlobalScopes()->latest('id')->first();
    };
});

it('stores the note the app sends and returns it', function (): void {
    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'notes' => 'Gift wrap, collect tomorrow',
    ]))->assertSuccessful();

    expect($response->json('data.notes'))->toBe('Gift wrap, collect tomorrow')
        ->and(Sale::withoutGlobalScopes()->find($response->json('data.id'))->notes)->toBe('Gift wrap, collect tomorrow');
});

it('replaces and clears the note on an edit', function (): void {
    $sale = ($this->parkDraft)(['notes' => 'Gift wrap']);

    $this->putJson($this->world->url('/api/v1/sale/'.$sale->id), ($this->editPayload)($sale, ['notes' => 'Deliver after 6pm']))
        ->assertSuccessful();
    expect($sale->fresh()->notes)->toBe('Deliver after 6pm');

    $this->putJson($this->world->url('/api/v1/sale/'.$sale->id), ($this->editPayload)($sale->fresh('items'), ['notes' => '']))
        ->assertSuccessful();
    expect($sale->fresh()->notes)->toBeNull();
});

it('keeps the note when an older app build edits the sale without the field', function (): void {
    $sale = ($this->parkDraft)(['notes' => 'Gift wrap']);

    $this->putJson($this->world->url('/api/v1/sale/'.$sale->id), ($this->editPayload)($sale))
        ->assertSuccessful();

    expect($sale->fresh()->notes)->toBe('Gift wrap');
});

it('refuses a note longer than 1000 characters', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'notes' => str_repeat('a', 1001),
    ]))->assertUnprocessable()->assertJsonValidationErrors('notes');
});

it('hands the note to the web POS when the sale is re-opened', function (): void {
    $sale = ($this->parkDraft)(['notes' => 'Gift wrap']);

    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'name' => 'sale.create', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    $this->withoutMiddleware(RequireOpenDaySession::class);

    $this->get($this->world->url(route('sale::pos', ['id' => $sale->id], absolute: false)))
        ->assertInertia(fn ($page) => $page->where('saleData.notes', 'Gift wrap'));
});
