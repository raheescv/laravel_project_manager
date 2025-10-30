<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class GetProductRequest extends FormRequest
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
            'main_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:name,price,mrp,cost'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'main_category_id.exists' => 'The selected category does not exist.',
            'sub_category_id.exists' => 'The selected sub category does not exist.',
            'brand_id.exists' => 'The selected brand does not exist.',
            'branch_id.exists' => 'The selected branch does not exist.',
            'sort_by.in' => 'The sort by field must be one of: name, price, mrp, cost.',
            'sort_direction.in' => 'The sort direction must be either asc or desc.',
            'per_page.max' => 'The per page value cannot exceed 100.',
        ];
    }

    /**
     * Get the validated data with defaults.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        return array_merge([
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 15,
            'page' => 1,
        ], $validated);
    }
}
