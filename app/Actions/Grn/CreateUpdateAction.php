<?php

namespace App\Actions\Grn;

use App\Models\Grn;

class CreateUpdateAction
{
    public function execute(array $data, int $userId, ?int $grnId = null)
    {
        try {
            validationHelper($this->rules(), $data);

            $grnNo = $data['grn_no'] ?? $this->generateGrnNo();

            $saveData = [
                'grn_no' => $grnNo,
                'local_purchase_order_id' => $data['local_purchase_order_id'],
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
                    'quantity' => $item['quantity'],
                ];

                $grn->items()->updateOrCreate($findData, $saveItemData);
            }

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
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    private function generateGrnNo(): string
    {
        $prefix = 'GRN-'.date('Ymd').'-';
        $lastGrn = Grn::where('grn_no', 'like', $prefix.'%')->orderBy('grn_no', 'desc')->first();

        if ($lastGrn) {
            $lastNumber = (int) str_replace($prefix, '', $lastGrn->grn_no);

            return $prefix.str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return $prefix.'001';
    }
}
