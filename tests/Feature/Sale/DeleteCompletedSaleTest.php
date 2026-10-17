<?php

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\DeleteAction;
use App\Models\Journal;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * A completed sale has already moved stock and posted journals, so only a
 * super admin may delete it — and deleting it puts both back.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100);
    $this->inventoryId = DB::table('inventories')
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->value('id');
});

function completedSaleForDeletion(PosWorld $world, int $inventoryId): Sale
{
    $response = (new CreateAction())->execute([
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
            'quantity' => 2,
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
    ], $world->user->id);

    expect($response['success'])->toBeTrue($response['message'] ?? '');

    return $response['data'];
}

it('refuses to delete a completed sale for a user who is not a super admin', function (): void {
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $sale = completedSaleForDeletion($this->world, $this->inventoryId);

    $response = (new DeleteAction())->execute($sale->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toBe('Completed sale cant be deleted')
        ->and(Sale::withoutGlobalScopes()->find($sale->id)->trashed())->toBeFalse();
});

it('lets a super admin delete a completed sale, restoring stock and removing its journals', function (): void {
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $sale = completedSaleForDeletion($this->world, $this->inventoryId);

    expect((float) DB::table('inventories')->where('id', $this->inventoryId)->value('quantity'))->toBe(98.0)
        ->and(Journal::withoutGlobalScopes()->where('model', 'Sale')->where('model_id', $sale->id)->exists())->toBeTrue();

    $response = (new DeleteAction())->execute($sale->id, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(Sale::withoutGlobalScopes()->find($sale->id)->trashed())->toBeTrue()
        ->and((float) DB::table('inventories')->where('id', $this->inventoryId)->value('quantity'))->toBe(100.0)
        ->and(Journal::withoutGlobalScopes()->whereNull('deleted_at')->where('model', 'Sale')->where('model_id', $sale->id)->exists())->toBeFalse();
});
