<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductCatalogueRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'colour' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:30'],
            'group' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'The name filter can be at most 100 characters.',
            'sku.max' => 'The SKU filter can be at most 100 characters.',
            'barcode.max' => 'The barcode filter can be at most 100 characters.',
            'colour.max' => 'The colour filter can be at most 50 characters.',
            'size.max' => 'The size filter can be at most 30 characters.',
        ];
    }

    /**
     * @return array{name: string, sku: string, barcode: string, colour: string, size: string}
     */
    public function filters(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'sku' => trim((string) $this->input('sku')),
            'barcode' => trim((string) $this->input('barcode')),
            'colour' => trim((string) $this->input('colour')),
            'size' => trim((string) $this->input('size')),
        ];
    }
}
