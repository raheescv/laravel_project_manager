<?php

namespace App\Http\Requests\V1\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The mobile Add / Edit form for a product or service. Shape checks only — the
 * domain rules (unique name per type, required unit for a product, unique
 * barcode) stay in Product::rules, which the web CreateAction/UpdateAction run.
 *
 * Department, brand and the categories accept an id or `add <name>`, the web
 * form's quick-create convention.
 */
class SaveRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'name_arabic' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'disabled'])],
            'department_id' => ['required'],
            'main_category_id' => ['required'],
            'sub_category_id' => ['nullable'],
            'unit_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'string', 'max:50'],
            'size_category' => ['nullable', 'string', 'max:100'],
            'hsn_code' => ['nullable', 'string', 'max:50'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'barcode_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_selling' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'time' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a name.',
            'department_id.required' => 'Choose a department.',
            'main_category_id.required' => 'Choose a main category.',
            'mrp.required' => 'Enter the selling price.',
            'tax.max' => 'Tax cannot be more than 100%.',
        ];
    }
}
