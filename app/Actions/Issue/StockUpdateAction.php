<?php

namespace App\Actions\Issue;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\IssueItem;
use Exception;

class StockUpdateAction
{
    public function execute($issue, $user_id, $issue_type = 'issue'): array
    {
        try {
            foreach ($issue->items as $item) {
                $this->singleItem($item, $issue, $issue_type, $user_id);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Inventory';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * Run stock update for only the given items (e.g. only changed rows).
     */
    public function executeForItems($issue, iterable $items, $user_id, string $issue_type = 'issue'): array
    {
        try {
            foreach ($items as $item) {
                $this->singleItem($item, $issue, $issue_type, $user_id);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Inventory';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    public function singleItem(IssueItem $item, $issue, string $issue_type, $user_id): void
    {
        $inventory = $this->findInventory($item);

        $inventoryData = $inventory->toArray();
        $this->applyIssueToInventory($inventoryData, $item, $issue, $issue_type);
        $inventoryData['model'] = 'Issue';
        $inventoryData['model_id'] = $issue->id;
        $inventoryData['updated_by'] = $user_id;

        $this->saveInventory($inventoryData, $inventory->id);
    }

    private function findInventory(IssueItem $item): Inventory
    {
        $branchId = session('branch_id');

        if (! empty($item->inventory_id)) {
            $inventoryQuery = Inventory::withoutGlobalScopes()->where('id', $item->inventory_id);
            if ($branchId) {
                $inventoryQuery->where('branch_id', $branchId);
            }
            $inventory = $inventoryQuery->first();

            if (! $inventory) {
                throw new Exception('Inventory not found for inventory ID: '.$item->inventory_id, 1);
            }

            return $inventory;
        }

        if (! $branchId) {
            throw new Exception('Branch context is required to update inventory for issue.', 1);
        }

        $inventory = Inventory::withoutGlobalScopes()
            ->where('product_id', $item->product_id)
            ->where('branch_id', $branchId)
            ->first();

        if (! $inventory) {
            throw new Exception('Inventory not found for product ID: '.$item->product_id, 1);
        }

        return $inventory;
    }

    private function applyIssueToInventory(array &$inventoryData, IssueItem $item, $issue, string $issue_type): void
    {
        $quantityOut = (float) $item->quantity_out;
        $quantityIn = (float) $item->quantity_in;

        if ($issue_type === 'issue') {
            $inventoryData['quantity'] -= $quantityOut;
            $inventoryData['quantity'] += $quantityIn;
            $inventoryData['remarks'] = $this->getRemarks('Issue', $issue);
        } else {
            // cancel / reversal: reverse the effect
            $inventoryData['quantity'] += $quantityOut;
            $inventoryData['quantity'] -= $quantityIn;
            $inventoryData['remarks'] = $this->getRemarks($this->getRemarksPrefix($issue_type), $issue);
        }
    }

    private function getRemarksPrefix(string $issue_type): string
    {
        return match ($issue_type) {
            'cancel' => 'Issue Cancelled',
            'reversal' => 'Issue Reversal',
            default => 'Issue',
        };
    }

    private function getRemarks(string $prefix, $issue): string
    {
        $reference = 'Issue #'.$issue->id;

        return $prefix.': '.$reference;
    }

    private function saveInventory(array $inventoryData, int $inventoryId): void
    {
        $response = (new UpdateAction())->execute($inventoryData, $inventoryId);

        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}
