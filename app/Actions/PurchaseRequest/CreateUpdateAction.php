<?php

namespace App\Actions\PurchaseRequest;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;

class CreateUpdateAction
{
    public function execute($data, ?int $purchase_request_id = null)
    {
        try {
            validationHelper($this->rules(), $data);

            $return = [];
            DB::transaction(function () use ($data, &$return, $purchase_request_id) {
                $purchase_request = PurchaseRequest::updateOrCreate([
                    'id' => $purchase_request_id,
                ], [
                    'branch_id' => session('branch_id'),
                    'tenant_id' => session('tenant_id'),
                ]);

                $deletedIds = $purchase_request->products()->whereNotIn('id', collect($data['products'])->pluck('id'))->pluck('id');

                $purchase_request->products()->whereIn('id', $deletedIds)->delete();

                foreach ($data['products'] as $product) {
                    $product = $purchase_request->products()->updateOrCreate([
                        'id' => $product['id'] ?? null,
                        'purchase_request_id' => $purchase_request->id,
                    ], [
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                    ]);
                }

                $return['success'] = true;
                $return['message'] = __('Successfully :action purchase request', ['action' => $purchase_request_id ? 'Updated' : 'Created']);
                $return['data'] = $purchase_request;
            });
        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
