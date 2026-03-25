<?php

namespace App\Actions\LocalPurchaseOrder;

use App\Models\LocalPurchaseOrder;
use App\Models\LocalPurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateAction
{
    public function execute(array $data)
    {
        DB::beginTransaction();

        try {
            validationHelper($this->rules(), $data);

            $order = LocalPurchaseOrder::create([
                'vendor_id' => $data['vendor_id'],
                'tenant_id' => session('tenant_id'),
                'branch_id' => session('branch_id'),
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['rate'];

                LocalPurchaseOrderItem::create([
                    'local_purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $order->update(['total_amount' => $total]);

            DB::commit();

            return [
                'success' => true,
                'data' => $order,
                'message' => 'LPO created successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function rules(): array
    {
        return [
            'vendor_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('model', 'Vendor')),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
