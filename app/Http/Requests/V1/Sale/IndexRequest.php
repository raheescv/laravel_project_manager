<?php

namespace App\Http\Requests\V1\Sale;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:draft,completed,cancelled'],
            'sale_type' => ['nullable', 'string', 'max:50'],
            'customer_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'sort_by' => ['nullable', 'string', 'in:date,invoice_no,paid,gross_amount,id'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'mine_only' => ['nullable', 'boolean'],
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
            'status.in' => 'The status must be one of: draft, completed, cancelled.',
            'sort_by.in' => 'The sort by field must be one of: date, invoice_no, paid, gross_amount, id.',
            'sort_direction.in' => 'The sort direction must be either asc or desc.',
            'per_page.max' => 'The per page value cannot exceed 100.',
            'to_date.after_or_equal' => 'The to date must be on or after the from date.',
        ];
    }

    /**
     * Get the validated data with defaults.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        return array_merge([
            'sort_by' => 'date',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'page' => 1,
            'mine_only' => false,
        ], $this->validated());
    }
}
