<?php

namespace App\Actions\InventoryTransfer;

use App\Actions\Journal\CreateAction;
use App\Actions\Journal\UpdateAction;
use App\Models\InventoryTransfer;
use App\Models\Journal;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public int $userId;

    public function execute(InventoryTransfer $inventoryTransfer, int $userId): array
    {
        try {
            $this->userId = $userId;
            $inventoryTransfer->loadMissing('items.inventory', 'fromBranch', 'toBranch');

            $accounts = Cache::get('accounts_slug_id_map', []);
            if (empty($accounts['inventory'])) {
                throw new \Exception('Required account head is missing: inventory.');
            }

            $totalValue = $inventoryTransfer->items->sum(function ($item): float {
                return (float) $item->quantity * (float) ($item->inventory?->cost ?? 0);
            });

            $existingJournal = Journal::withoutGlobalScopes()
                ->where('model', 'InventoryTransfer')
                ->where('model_id', $inventoryTransfer->id)
                ->first();

            if ($totalValue <= 0) {
                if ($existingJournal) {
                    $existingJournal->entries()->delete();
                    $existingJournal->delete();
                }

                return [
                    'success' => true,
                    'message' => 'No journal entries needed (zero value).',
                ];
            }

            $data = $this->buildJournalData($inventoryTransfer, (int) $accounts['inventory'], (float) $totalValue);

            $response = $existingJournal
                ? (new UpdateAction())->execute($data, $existingJournal->id)
                : (new CreateAction())->execute($data);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            return [
                'success' => true,
                'data' => $response['data'] ?? null,
                'message' => 'Successfully Created Inventory Transfer Journal',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    protected function buildJournalData(InventoryTransfer $inventoryTransfer, int $inventoryAccountId, float $totalValue): array
    {
        $fromBranchName = $inventoryTransfer->fromBranch?->name ?? 'Source Branch';
        $toBranchName = $inventoryTransfer->toBranch?->name ?? 'Destination Branch';
        $remarks = 'InventoryTransfer:'.$inventoryTransfer->id.' ['.$fromBranchName.' -> '.$toBranchName.']';

        return [
            'tenant_id' => $inventoryTransfer->tenant_id,
            'date' => $inventoryTransfer->date,
            'branch_id' => $inventoryTransfer->from_branch_id,
            'description' => 'InventoryTransfer:'.$inventoryTransfer->id,
            'reference_number' => 'IT-'.$inventoryTransfer->id,
            'source' => 'inventory_transfer',
            'model' => 'InventoryTransfer',
            'model_id' => $inventoryTransfer->id,
            'created_by' => $this->userId,
            'entries' => [
                [
                    'branch_id' => $inventoryTransfer->to_branch_id,
                    'account_id' => $inventoryAccountId,
                    'counter_account_id' => $inventoryAccountId,
                    'debit' => $totalValue,
                    'credit' => 0,
                    'remarks' => $remarks,
                    'model' => 'InventoryTransfer',
                    'model_id' => $inventoryTransfer->id,
                    'created_by' => $this->userId,
                ],
                [
                    'branch_id' => $inventoryTransfer->from_branch_id,
                    'account_id' => $inventoryAccountId,
                    'counter_account_id' => $inventoryAccountId,
                    'debit' => 0,
                    'credit' => $totalValue,
                    'remarks' => $remarks,
                    'model' => 'InventoryTransfer',
                    'model_id' => $inventoryTransfer->id,
                    'created_by' => $this->userId,
                ],
            ],
        ];
    }
}
