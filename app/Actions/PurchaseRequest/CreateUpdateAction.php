<?php

namespace App\Actions\PurchaseRequest;

use App\Models\PurchaseRequest;

class CreateUpdateAction
{
    public function execute($data, $userId, ?int $purchase_request_id = null)
    {
        try {
            validationHelper($this->rules(), $data);

            $return = [];
            $purchase_request = PurchaseRequest::updateOrCreate(
                ['id' => $purchase_request_id],
                [
                    'branch_id' => session('branch_id'),
                    'tenant_id' => session('tenant_id'),
                    'created_by' => $userId,
                ],
            );

            $deletedIds = $purchase_request
                ->products()
                ->whereNotIn('id', collect($data)->pluck('id')->filter())
                ->pluck('id');

            $purchase_request->products()->whereIn('id', $deletedIds)->delete();

            foreach ($data as $product) {
                $findData = [
                    'id' => $product['id'] ?? null,
                    'purchase_request_id' => $purchase_request->id,
                ];
                $updateData = [
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                ];
                $product = $purchase_request->products()->updateOrCreate($findData, $updateData);
            }

            $return['success'] = true;
            $return['message'] = __('Successfully :action purchase request', ['action' => $purchase_request_id ? 'Updated' : 'Created']);
            $return['data'] = $purchase_request;
        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function rules(): array
    {
        return [
            '*.product_id' => ['required', 'exists:products,id'],
            '*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
