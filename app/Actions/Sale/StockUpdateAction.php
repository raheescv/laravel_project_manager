<?php

namespace App\Actions\Sale;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockUpdateAction
{
    public function execute($sale, $user_id, $sale_type = 'sale')
    {
        try {
            foreach ($sale->items as $item) {
                $this->singleItem($item, $sale, $sale_type, $user_id);
            }
            return [
                'success' => true,
                'message' => 'Successfully Updated Inventory',
                'data' => [],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function singleItem($item, $sale, $sale_type, $user_id)
    {
        /* =============================
           FINISHED PRODUCT STOCK (OLD FLOW)
        ============================== */
        $inventory = Inventory::find($item->inventory_id);
        if (! $inventory) {
            throw new \Exception('inventory not found', 1);
        }
        $inventoryData = $inventory->toArray();

        switch ($sale_type) {
            case 'sale':
                $inventoryData['quantity'] -= $item->quantity;
                $inventoryData['remarks'] = 'Sale:' . $sale->invoice_no;
                break;
            case 'cancel':
                $inventoryData['quantity'] += $item->quantity;
                $inventoryData['remarks'] = 'Sale Cancelled:' . $sale->invoice_no;
                break;
            case 'sale_reversal':
                $inventoryData['quantity'] += $item->quantity;
                $inventoryData['remarks'] = 'Sale Adjustment Reversal:' . $sale->invoice_no;
                break;
            case 'delete_item':
                $inventoryData['quantity'] += $item->quantity;
                $inventoryData['remarks'] = 'Sale Item Delete:' . $sale->invoice_no;
                break;
        }

        $inventoryData['model'] = 'Sale';
        $inventoryData['model_id'] = $sale->id;
        $inventoryData['updated_by'] = $user_id;

        $response = (new UpdateAction())->execute($inventoryData, $inventoryData['id']);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }

        /* =============================
           RAW MATERIAL STOCK (NEW FLOW)
           Only for completed bookings
        ============================== */
        if ($sale->status === 'completed' && $sale->type === 'booking') {

            $rawMaterials = DB::table('product_raw_materials')
                ->where('product_id', $item->product_id)
                ->where('status', 'active')
                ->get();

            Log::debug('Raw materials fetched', [
                'finished_product_id' => $item->product_id,
                'count' => $rawMaterials->count(),
                'data' => $rawMaterials->toArray(),
            ]);

            foreach ($rawMaterials as $rm) {
                $rawProductId = (int) $rm->name; // name column = raw product id
                $deductQty = (float) $rm->price ; // price column = quantity to deduct

                $rawInventory = Inventory::where('product_id', $rawProductId)
                    ->where('branch_id', $inventory->branch_id) // same branch as finished product
                    ->first();

                if (! $rawInventory) {
                    throw new \Exception(
                        'Raw material inventory not found for raw product ID ' . $rawProductId
                    );
                }

                $rawData = $rawInventory->toArray();
                $rawData['quantity'] -= $deductQty;
                $rawData['remarks'] = 'Used in booking sale:' . $sale->invoice_no;
                $rawData['model'] = 'Sale';
                $rawData['model_id'] = $sale->id;
                $rawData['updated_by'] = $user_id;

                $resp = (new UpdateAction())->execute($rawData, $rawData['id']);
                if (! $resp['success']) {
                    throw new \Exception($resp['message']);
                }

                Log::debug('Raw inventory updated', [
                    'raw_product_id' => $rawProductId,
                    'deduct_qty' => $deductQty,
                    'new_qty' => $rawData['quantity'],
                ]);
            }
        }
    }
}
