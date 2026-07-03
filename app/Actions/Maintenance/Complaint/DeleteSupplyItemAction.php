<?php

namespace App\Actions\Maintenance\Complaint;

use App\Actions\Maintenance\Complaint\Concerns\ManagesSupplyRequest;
use App\Models\SupplyRequestItem;
use Illuminate\Support\Facades\DB;

/**
 * Delete a supply item and recompute totals. Mirrors Complaint::deleteItem.
 * Shared by web + mobile (each caller applies its own permission gate).
 */
class DeleteSupplyItemAction
{
    use ManagesSupplyRequest;

    public function execute($itemId)
    {
        try {
            $item = SupplyRequestItem::find($itemId);
            if (! $item) {
                throw new \Exception("Supply Request Item not found with the specified ID: $itemId.", 1);
            }

            $sr = $item->supplyRequest;

            DB::transaction(function () use ($item, $sr) {
                $item->delete();
                if ($sr) {
                    $this->recalculateSupplyTotals($sr);
                }
            });

            $return['success'] = true;
            $return['message'] = 'Successfully deleted item';
            $return['data'] = $sr;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
