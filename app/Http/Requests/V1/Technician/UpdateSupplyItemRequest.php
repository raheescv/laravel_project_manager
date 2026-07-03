<?php

namespace App\Http\Requests\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'mode' => ['nullable', 'string', 'in:New,Damaged'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
