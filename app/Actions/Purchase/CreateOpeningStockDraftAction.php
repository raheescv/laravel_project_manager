<?php

namespace App\Actions\Purchase;

use App\Models\Branch;
use App\Models\Product;
use App\Support\OpeningStock;

/**
 * Records a branch's opening stock as one draft purchase against the Opening Stock account,
 * priced at each product's buying price, or half its selling price when it has no buying price. Completing the draft brings the stock in and posts it.
 */
class CreateOpeningStockDraftAction
{
    /**
     * @param  array<int, float>  $quantities  product id => positive opening quantity
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function execute(int $branchId, array $quantities, int $userId): array
    {
        try {
            if (empty($quantities)) {
                throw new \Exception('There is no opening stock to record.', 1);
            }

            $branch = Branch::find($branchId, ['id', 'code']);
            if (! $branch) {
                throw new \Exception("Branch not found with the specified ID: $branchId.", 1);
            }

            $products = Product::whereIn('id', array_keys($quantities))->get(['id', 'cost', 'mrp', 'unit_id'])->keyBy('id');

            $items = [];
            foreach ($quantities as $productId => $quantity) {
                $product = $products[$productId] ?? null;
                if (! $product) {
                    continue;
                }
                $items[] = [
                    'product_id' => $product->id,
                    'unit_id' => $product->unit_id ?: 1,
                    'conversion_factor' => 1,
                    'unit_price' => (float) $product->cost > 0 ? (float) $product->cost : round($product->mrp / 2, 2),
                    'quantity' => $quantity,
                    'discount' => 0,
                    'tax' => 0,
                ];
            }

            $total = round(array_sum(array_map(fn ($item) => $item['unit_price'] * $item['quantity'], $items)), 2);

            $data = [
                'invoice_no' => 'OPENING-'.($branch->code ?: $branch->id).'-'.now()->format('Ymd-His'),
                'account_id' => OpeningStock::accountId(),
                'branch_id' => $branch->id,
                'date' => now()->toDateString(),
                'delivery_date' => now()->toDateString(),
                'gross_amount' => $total,
                'item_discount' => 0,
                'tax_amount' => 0,
                'total' => $total,
                'other_discount' => 0,
                'freight' => 0,
                'status' => 'draft',
                'items' => $items,
                'payments' => [],
            ];

            return (new CreateAction())->execute($data, $userId);
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
