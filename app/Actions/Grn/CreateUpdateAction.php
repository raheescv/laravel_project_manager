<?php

namespace App\Actions\Grn;

use App\Models\Grn;
use App\Models\LocalPurchaseOrder;

class CreateUpdateAction
{
    public function execute(array $data, int $userId, ?int $grnId = null)
    {
        try {
            validationHelper($this->rules(), $data);

            $lpo = LocalPurchaseOrder::findOrFail($data['local_purchase_order_id']);

            $saveData = [
                'grn_no' => $data['grn_no'] ?? generateGrnNo(),
                'local_purchase_order_id' => $lpo->id,
                'vendor_id' => $lpo->vendor_id,
                'date' => $data['date'],
                'remarks' => $data['remarks'] ?? null,
                'tenant_id' => session('tenant_id'),
                'branch_id' => session('branch_id'),
                'created_by' => $userId,
            ];

            $grn = Grn::updateOrCreate(['id' => $grnId], $saveData);

            $existingItemIds = collect($data['items'])->pluck('id')->filter();
            $grn->items()->whereNotIn('id', $existingItemIds)->delete();

            foreach ($data['items'] as $item) {
                $findData = [
                    'id' => $item['id'] ?? null,
                    'grn_id' => $grn->id,
                ];
                $saveItemData = [
                    'tenant_id' => session('tenant_id'),
                    'local_purchase_order_item_id' => $item['local_purchase_order_item_id'],
                    'product_id' => $item['product_id'],
                    'account_id' => $item['account_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'] ?? 0,
                ];

                $grn->items()->updateOrCreate($findData, $saveItemData);
            }
            $grn->items()->where('quantity', 0)->delete();

            return [
                'success' => true,
                'data' => $grn,
                'message' => __('Successfully :action GRN', ['action' => $grnId ? 'Updated' : 'Created']),
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ];
    }
}
