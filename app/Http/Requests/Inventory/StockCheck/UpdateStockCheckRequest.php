<?php

namespace App\Http\Requests\Inventory\StockCheck;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stock_check_items,id',
            'items.*.physical_quantity' => 'required|numeric|min:0',
            'items.*.status' => 'sometimes|in:pending,completed',
        ];
    }
}
