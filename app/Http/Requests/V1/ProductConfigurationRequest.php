<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barcode_type' => ['sometimes', 'nullable', Rule::in(array_keys(barcodeTypes()))],
            'barcode_prefix' => ['sometimes', 'nullable', 'string', 'max:10'],
            'sync_barcode_to_code' => ['sometimes', Rule::in(['yes', 'no'])],
            'hide_out_of_stock_sale_items' => ['sometimes', Rule::in(['yes', 'no'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'barcode_type.in' => 'Barcode type must be Product Wise or System Generation.',
            'barcode_prefix.max' => 'The barcode prefix can be at most 10 characters.',
        ];
    }
}
