<?php

use App\Actions\InventoryTransfer\DeleteAction;
use App\Actions\InventoryTransfer\JournalEntryAction;
use App\Actions\InventoryTransfer\StockUpdateAction;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\InventoryTransfer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create(stock: 10);
    $this->toBranch = $this->world->addBranch('Doha Branch', 'DB');
    TenantCache::forget('branches');
});

function transferQuantity(int $productId, int $branchId): float
{
    return (float) Inventory::query()
        ->where('product_id', $productId)
        ->where('branch_id', $branchId)
        ->whereNull('employee_id')
        ->value('quantity');
}

function makeTransfer(PosWorld $world, int $toBranchId, string $status): InventoryTransfer
{
    $transfer = InventoryTransfer::create([
        'tenant_id' => $world->tenant->id,
        'date' => now()->toDateString(),
        'branch_id' => $world->branch->id,
        'from_branch_id' => $world->branch->id,
        'to_branch_id' => $toBranchId,
        'status' => $status,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
    ]);
    $transfer->items()->create([
        'product_id' => $world->product->id,
        'inventory_id' => Inventory::query()->where('product_id', $world->product->id)->where('branch_id', $world->branch->id)->value('id'),
        'quantity' => 4,
    ]);

    if ($status === 'completed') {
        expect((new StockUpdateAction())->execute($transfer, $world->user->id)['success'])->toBeTrue()
            ->and((new JournalEntryAction())->execute($transfer, $world->user->id)['success'])->toBeTrue();
    }

    return $transfer;
}

it('lets a super admin delete a completed transfer, reversing stock and the journal', function (): void {
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $transfer = makeTransfer($this->world, $this->toBranch->id, 'completed');

    expect(transferQuantity($this->world->product->id, $this->world->branch->id))->toBe(6.0)
        ->and(transferQuantity($this->world->product->id, $this->toBranch->id))->toBe(104.0);

    $response = (new DeleteAction())->execute($transfer->id, $this->world->user->id);

    expect($response['success'])->toBeTrue()
        ->and(InventoryTransfer::find($transfer->id))->toBeNull()
        ->and(transferQuantity($this->world->product->id, $this->world->branch->id))->toBe(10.0)
        ->and(transferQuantity($this->world->product->id, $this->toBranch->id))->toBe(100.0)
        ->and(Journal::query()->where('model', 'InventoryTransfer')->where('model_id', $transfer->id)->exists())->toBeFalse()
        ->and(JournalEntry::query()->where('model', 'InventoryTransfer')->where('model_id', $transfer->id)->exists())->toBeFalse()
        ->and(Journal::onlyTrashed()->where('model', 'InventoryTransfer')->where('model_id', $transfer->id)->value('deleted_by'))->toBe($this->world->user->id);
});

it('refuses to delete a completed transfer for a non super admin', function (): void {
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $transfer = makeTransfer($this->world, $this->toBranch->id, 'completed');

    $response = (new DeleteAction())->execute($transfer->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toBe('Completed inventory transfer cant be deleted')
        ->and(InventoryTransfer::find($transfer->id))->not->toBeNull()
        ->and(transferQuantity($this->world->product->id, $this->toBranch->id))->toBe(104.0);
});

it('still deletes a pending transfer without touching stock', function (): void {
    $transfer = makeTransfer($this->world, $this->toBranch->id, 'pending');

    $response = (new DeleteAction())->execute($transfer->id, $this->world->user->id);

    expect($response['success'])->toBeTrue()
        ->and(InventoryTransfer::find($transfer->id))->toBeNull()
        ->and(transferQuantity($this->world->product->id, $this->world->branch->id))->toBe(10.0);
});

it('names both branches in every transfer and reversal stock remark', function (): void {
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $transfer = makeTransfer($this->world, $this->toBranch->id, 'completed');

    expect((new DeleteAction())->execute($transfer->id, $this->world->user->id)['success'])->toBeTrue();

    $logs = InventoryLog::query()->where('model', 'InventoryTransfer')->where('model_id', $transfer->id)->orderBy('id')->get(['branch_id', 'remarks']);

    expect($logs->map(fn ($log) => [$log->branch_id, $log->remarks])->all())->toBe([
        [$this->world->branch->id, "InventoryTransfer:{$transfer->id} [Main Branch -> Doha Branch]"],
        [$this->toBranch->id, "InventoryTransfer:{$transfer->id} [Doha Branch <- Main Branch]"],
        [$this->toBranch->id, "InventoryTransfer Adjustment Reversal:{$transfer->id} [Doha Branch -> Main Branch]"],
        [$this->world->branch->id, "InventoryTransfer Adjustment Reversal:{$transfer->id} [Main Branch <- Doha Branch]"],
    ]);
});
