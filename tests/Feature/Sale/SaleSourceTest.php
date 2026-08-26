<?php

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\UpdateAction;
use App\Models\Sale;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * `sales.source` records the channel a sale was rung up on — the web screens,
 * the POS, the mobile app's API, an import. It is written once, at creation,
 * and is the only way to tell after the fact where an invoice came from.
 *
 * @see saleSources()
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
});

/** The payload App\Actions\Sale\CreateAction expects, for this world. */
function sourceTestSalePayload(PosWorld $world, array $overrides = []): array
{
    $inventoryId = DB::table('inventories')
        ->where('product_id', $world->product->id)
        ->where('branch_id', $world->branch->id)
        ->value('id');

    return array_merge([
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => today()->toDateString(),
        'sale_type' => 'normal',
        'status' => 'completed',
        'gross_amount' => 50,
        'item_discount' => 0,
        'tax_amount' => 0,
        'other_discount' => 0,
        'freight' => 0,
        'round_off' => 0,
        'paid' => 50,
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
        'payments' => [[
            'payment_method_id' => $world->cashAccountId,
            'amount' => 50,
            'date' => today()->toDateString(),
        ]],
        'comboOffers' => [],
    ], $overrides);
}

it('records a sale posted to the mobile API as an API sale', function (): void {
    Sanctum::actingAs($this->world->user);

    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('data.id'));

    expect($sale->source)->toBe('api')
        ->and($sale->source_label)->toBe('API');
});

it('records the channel each caller declares', function (string $source): void {
    $response = (new CreateAction())->execute(
        sourceTestSalePayload($this->world, ['source' => $source]),
        $this->world->user->id,
    );

    expect($response['success'])->toBeTrue()
        ->and($response['data']->source)->toBe($source);
})->with(['web', 'pos', 'import', 'appointment', 'migration']);

it('refuses a source the request made up', function (): void {
    // POSController and the Inertia POS hand their whole request body to the
    // action, so an unrecognised `source` in it is untrusted input, not a
    // channel. It is dropped and the real channel inferred instead — otherwise
    // any client could label its sales whatever it liked.
    $response = (new CreateAction())->execute(
        sourceTestSalePayload($this->world, ['source' => 'trust-me']),
        $this->world->user->id,
    );

    expect($response['data']->source)->toBe('web');
});

it('keeps the original source when the sale is later edited', function (): void {
    $created = (new CreateAction())->execute(
        sourceTestSalePayload($this->world, ['source' => 'api', 'status' => 'draft']),
        $this->world->user->id,
    );

    // The back office opens the mobile sale on the web and saves it. That is an
    // edit, not a re-origination: it must not relabel where the sale came from.
    $sale = $created['data']->fresh(['items', 'payments']);
    $edit = sourceTestSalePayload($this->world, ['source' => 'web', 'status' => 'draft']);
    $edit['total'] = 50;
    $edit['grand_total'] = 50;
    // The existing lines carry their ids, exactly as an edit screen posts them —
    // without which the update would try to add the same product a second time.
    $edit['items'][0]['id'] = $sale->items->first()->id;
    $edit['payments'][0] = array_merge(
        $edit['payments'][0],
        $sale->payments->first()->only(['id', 'created_by', 'updated_by']),
    );

    $updated = (new UpdateAction())->execute($edit, $created['data']->id, $this->world->user->id);

    expect($updated['success'])->toBeTrue($updated['message'] ?? '')
        ->and(Sale::withoutGlobalScopes()->find($created['data']->id)->source)->toBe('api');
});

it('filters the sale list by source', function (): void {
    (new CreateAction())->execute(sourceTestSalePayload($this->world, ['source' => 'web']), $this->world->user->id);
    (new CreateAction())->execute(sourceTestSalePayload($this->world, ['source' => 'import']), $this->world->user->id);

    $sources = Sale::withoutGlobalScopes()->filter(['source' => 'import'])->pluck('source');

    expect($sources)->toHaveCount(1)->and($sources->first())->toBe('import');
});

it('reads as Unknown for a sale that predates the column', function (): void {
    $created = (new CreateAction())->execute(sourceTestSalePayload($this->world), $this->world->user->id);
    Sale::withoutGlobalScopes()->where('id', $created['data']->id)->update(['source' => null]);

    expect(Sale::withoutGlobalScopes()->find($created['data']->id)->source_label)->toBe('Unknown');
});
