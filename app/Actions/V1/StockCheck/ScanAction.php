<?php

namespace App\Actions\V1\StockCheck;

use App\Actions\Product\Inventory\StockCheck\Item\ScanBarcodeAction;
use App\Models\StockCheckItem;
use Exception;

/**
 * Increments the matched item's physical quantity by 1 (rapid physical
 * counting) via the shared web action, then enriches the result with the
 * product name/code so the app can show a scan confirmation toast.
 */
class ScanAction
{
    public function __construct(private ScanBarcodeAction $action) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $id, string $barcode): array
    {
        $response = $this->action->execute($id, $barcode);

        if (! $response['success']) {
            throw new Exception($response['message']);
        }

        $item = StockCheckItem::with('product:id,name,code')->findOrFail($response['data']['id']);

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name,
            'product_code' => $item->product?->code,
            'barcode' => $barcode,
            'physical_quantity' => (float) $item->physical_quantity,
            'recorded_quantity' => (float) $item->recorded_quantity,
            'difference' => (float) $item->difference,
            'status' => $item->status,
        ];
    }
}
