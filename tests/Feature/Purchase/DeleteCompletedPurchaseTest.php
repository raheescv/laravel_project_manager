<?php

use App\Actions\Purchase\DeleteAction;
use App\Actions\Purchase\JournalEntryAction;
use App\Actions\Purchase\StockUpdateAction;
use App\Models\Inventory;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create(stock: 10);
});

function purchaseStock(PosWorld $world): float
{
    return (float) Inventory::query()
        ->where('product_id', $world->product->id)
        ->where('branch_id', $world->branch->id)
        ->whereNull('employee_id')
        ->value('quantity');
}

function makePurchase(PosWorld $world, string $status): Purchase
{
    $purchase = Purchase::create([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => 'PUR-'.uniqid(),
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => now()->toDateString(),
        'gross_amount' => 50,
        'total' => 50,
        'status' => $status,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
    ]);
    $purchase->items()->create([
        'product_id' => $world->product->id,
        'unit_id' => $world->product->unit_id,
        'unit_price' => 10,
        'quantity' => 5,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
    ]);

    if ($status === 'completed') {
        expect((new StockUpdateAction())->execute($purchase->refresh(), $world->user->id)['success'])->toBeTrue()
            ->and((new JournalEntryAction())->execute($purchase->refresh(), $world->user->id)['success'])->toBeTrue();
    }

    return $purchase;
}

it('lets a super admin delete a completed purchase, taking the stock back out and dropping its journal', function (): void {
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $purchase = makePurchase($this->world, 'completed');

    expect(purchaseStock($this->world))->toBe(15.0)
        ->and(Journal::query()->where('model', 'Purchase')->where('model_id', $purchase->id)->exists())->toBeTrue();

    $response = (new DeleteAction())->execute($purchase->id, $this->world->user->id);

    expect($response['success'])->toBeTrue()
        ->and(Purchase::find($purchase->id))->toBeNull()
        ->and(purchaseStock($this->world))->toBe(10.0)
        ->and(Journal::query()->where('model', 'Purchase')->where('model_id', $purchase->id)->exists())->toBeFalse()
        ->and(JournalEntry::query()->where('model', 'Purchase')->where('model_id', $purchase->id)->exists())->toBeFalse();
});

it('refuses to delete a completed purchase for a non super admin', function (): void {
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $purchase = makePurchase($this->world, 'completed');

    $response = (new DeleteAction())->execute($purchase->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toBe('Completed purchase cant be deleted')
        ->and(Purchase::find($purchase->id))->not->toBeNull()
        ->and(purchaseStock($this->world))->toBe(15.0);
});

it('refuses to delete a completed purchase that has returns against it', function (): void {
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $purchase = makePurchase($this->world, 'completed');
    PurchaseReturnItem::query()->insert([
        'purchase_return_id' => 0,
        'purchase_item_id' => PurchaseItem::query()->where('purchase_id', $purchase->id)->value('id'),
        'product_id' => $this->world->product->id,
        'unit_id' => $this->world->product->unit_id,
        'unit_price' => 10,
        'quantity' => 1,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);

    $response = (new DeleteAction())->execute($purchase->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('has purchase returns against it')
        ->and(purchaseStock($this->world))->toBe(15.0);
});

it('still deletes a pending purchase without touching stock', function (): void {
    $purchase = makePurchase($this->world, 'pending');

    $response = (new DeleteAction())->execute($purchase->id, $this->world->user->id);

    expect($response['success'])->toBeTrue()
        ->and(Purchase::find($purchase->id))->toBeNull()
        ->and(purchaseStock($this->world))->toBe(10.0);
});
