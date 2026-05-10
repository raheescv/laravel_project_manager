<?php

use App\Actions\InventoryTransfer\JournalEntryAction;
use App\Models\Branch;
use App\Models\InventoryTransfer;

it('builds branch-aware inventory transfer journal entries', function (): void {
    $action = new class() extends JournalEntryAction
    {
        public function exposeBuildJournalData(InventoryTransfer $inventoryTransfer, int $inventoryAccountId, float $totalValue, int $userId): array
        {
            $this->userId = $userId;

            return $this->buildJournalData($inventoryTransfer, $inventoryAccountId, $totalValue);
        }
    };

    $inventoryTransfer = new InventoryTransfer([
        'tenant_id' => 7,
        'date' => '2026-05-10',
        'from_branch_id' => 11,
        'to_branch_id' => 12,
    ]);
    $inventoryTransfer->id = 45;
    $inventoryTransfer->setRelation('fromBranch', new Branch(['name' => 'Warehouse']));
    $inventoryTransfer->setRelation('toBranch', new Branch(['name' => 'Showroom']));

    $data = $action->exposeBuildJournalData($inventoryTransfer, 1001, 250.75, 9);

    expect($data['branch_id'])->toBe(11)
        ->and($data['description'])->toBe('InventoryTransfer:45')
        ->and($data['reference_number'])->toBe('IT-45')
        ->and($data['source'])->toBe('inventory_transfer')
        ->and($data['model'])->toBe('InventoryTransfer')
        ->and($data['model_id'])->toBe(45)
        ->and($data['entries'])->toHaveCount(2)
        ->and($data['entries'][0])->toMatchArray([
            'branch_id' => 12,
            'account_id' => 1001,
            'counter_account_id' => 1001,
            'debit' => 250.75,
            'credit' => 0,
            'remarks' => 'InventoryTransfer:45 [Warehouse -> Showroom]',
            'model' => 'InventoryTransfer',
            'model_id' => 45,
            'created_by' => 9,
        ])
        ->and($data['entries'][1])->toMatchArray([
            'branch_id' => 11,
            'account_id' => 1001,
            'counter_account_id' => 1001,
            'debit' => 0,
            'credit' => 250.75,
            'remarks' => 'InventoryTransfer:45 [Warehouse -> Showroom]',
            'model' => 'InventoryTransfer',
            'model_id' => 45,
            'created_by' => 9,
        ]);
});
