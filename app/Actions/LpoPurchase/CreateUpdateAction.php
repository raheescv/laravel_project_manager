<?php

namespace App\Actions\LpoPurchase;

use App\Enums\Purchase\PurchaseStatus;
use App\Models\LocalPurchaseOrder;
use App\Models\Purchase;

class CreateUpdateAction
{
    public function execute(array $data, int $userId, ?int $purchaseId = null)
    {
        try {
            validationHelper($this->rules(), $data);

            $lpo = LocalPurchaseOrder::with('vendor')->findOrFail($data['local_purchase_order_id']);

            $saveData = [
                'invoice_no' => $data['invoice_no'] ?? null,
                'local_purchase_order_id' => $lpo->id,
                'account_id' => $lpo->vendor_id,
                'date' => $data['date'],
                'status' => PurchaseStatus::PENDING->value,
                'remarks' => $data['remarks'] ?? null,
                'tenant_id' => session('tenant_id'),
                'branch_id' => session('branch_id'),
                'created_by' => $userId,
            ];

            $purchase = Purchase::updateOrCreate(['id' => $purchaseId], $saveData);

            // Remove items not in the new list
            $existingItemIds = collect($data['items'])->pluck('id')->filter();
            $purchase->items()->whereNotIn('id', $existingItemIds)->delete();

            $grossAmount = 0;
            $itemDiscount = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $item) {
                $quantity = $item['quantity'] ?? 0;
                $unitPrice = $item['unit_price'] ?? 0;
                $discount = $item['discount'] ?? 0;
                $tax = $item['tax'] ?? 0;

                $grossAmt = $quantity * $unitPrice;
                $netAmount = $grossAmt - $discount;
                $itemTaxAmount = $netAmount * ($tax / 100);
                $total = $netAmount + $itemTaxAmount;

                $grossAmount += $grossAmt;
                $itemDiscount += $discount;
                $taxAmount += $itemTaxAmount;

                $findData = [
                    'id' => $item['id'] ?? null,
                    'purchase_id' => $purchase->id,
                ];
                $saveItemData = [
                    'product_id' => $item['product_id'],
                    'account_id' => $item['account_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'tax' => $tax,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ];

                $purchase->items()->updateOrCreate($findData, $saveItemData);
            }

            // Update purchase totals
            $total = $grossAmount - $itemDiscount + $taxAmount;
            $purchase->update([
                'gross_amount' => $grossAmount,
                'item_discount' => $itemDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'other_discount' => $data['other_discount'] ?? 0,
                'freight' => $data['freight'] ?? 0,
            ]);

            return [
                'success' => true,
                'data' => $purchase,
                'message' => __('Successfully :action LPO Purchase', ['action' => $purchaseId ? 'Updated' : 'Created']),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function rules(): array
    {
        return [
            'local_purchase_order_id' => ['required', 'exists:local_purchase_orders,id'],
            'date' => ['required', 'date'],
            'invoice_no' => ['required'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
