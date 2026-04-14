<?php

namespace App\Actions\LocalPurchaseOrder;

use App\Models\LocalPurchaseOrder;
use Illuminate\Validation\Rule;

class CreateUpdateAction
{
    public function execute(array $data, int $userId, ?int $orderId = null)
    {
        try {
            validationHelper($this->rules(), $data);

            $total = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['rate']);

            $saveData = [
                'vendor_id' => $data['vendor_id'],
                'date' => $data['date'],
                'tenant_id' => session('tenant_id'),
                'branch_id' => session('branch_id'),
                'created_by' => $userId,
                'total' => $total,
            ];
            $order = LocalPurchaseOrder::updateOrCreate(['id' => $orderId], $saveData);

            $existingItemIds = collect($data['items'])->pluck('id')->filter();
            $order->items()->whereNotIn('id', $existingItemIds)->delete();

            foreach ($data['items'] as $item) {
                $findData = [
                    'id' => $item['id'] ?? null,
                    'local_purchase_order_id' => $order->id,
                ];
                $saveData = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                ];

                $order->items()->updateOrCreate($findData, $saveData);
            }

            return [
                'success' => true,
                'data' => $order,
                'message' => __('Successfully :action LPO', ['action' => $orderId ? 'Updated' : 'Created']),
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
            'vendor_id' => ['required', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('model', 'Vendor'))],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
