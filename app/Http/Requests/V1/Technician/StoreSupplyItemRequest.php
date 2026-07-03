<?php

namespace App\Http\Requests\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `product_id` OR `barcode` must be supplied (barcode resolves the product,
     * mirroring the web updated('item.barcode') flow).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id', 'required_without:barcode'],
            'barcode' => ['nullable', 'string', 'max:100', 'required_without:product_id'],
            'mode' => ['nullable', 'string', 'in:New,Damaged'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
