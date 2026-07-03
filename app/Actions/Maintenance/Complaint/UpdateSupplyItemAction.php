<?php

namespace App\Actions\Maintenance\Complaint;

use App\Actions\Maintenance\Complaint\Concerns\ManagesSupplyRequest;
use App\Models\SupplyRequestItem;
use Illuminate\Support\Facades\DB;

/**
 * Edit an existing supply item and recompute totals. Mirrors
 * Complaint::editCartItem (the save branch). Shared by web + mobile.
 */
class UpdateSupplyItemAction
{
    use ManagesSupplyRequest;

    /**
     * @param  array<string, mixed>  $data  any of quantity, unit_price, branch_id, mode, remarks
     */
    public function execute($itemId, array $data)
    {
        try {
            $item = SupplyRequestItem::find($itemId);
            if (! $item) {
                throw new \Exception("Supply Request Item not found with the specified ID: $itemId.", 1);
            }

            DB::transaction(function () use ($item, $data) {
                $item->update(array_filter([
                    'quantity' => $data['quantity'] ?? null,
                    'unit_price' => $data['unit_price'] ?? null,
                    'branch_id' => $data['branch_id'] ?? null,
                    'mode' => $data['mode'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                ], fn ($v) => $v !== null));

                $this->recalculateSupplyTotals($item->supplyRequest);
            });

            $return['success'] = true;
            $return['message'] = 'Item updated successfully';
            $return['data'] = $item;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
