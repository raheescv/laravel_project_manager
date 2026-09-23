<?php

namespace App\Http\Requests\V1\Report;

use Illuminate\Foundation\Http\FormRequest;

class GetRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:billwise,employeewise,itemwise,categorywise,commission,overview'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_type' => ['nullable', 'string', 'in:product,service,asset'],
            'sort' => ['nullable', 'string', 'in:amount,quantity,bills,name'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'type.in' => 'The report type must be billwise, employeewise, itemwise, categorywise, commission or overview.',
            'sort.in' => 'The sort must be amount, quantity, bills or name.',
            'direction.in' => 'The direction must be asc or desc.',
            'employee_id.exists' => 'The selected employee does not exist.',
            'product_id.exists' => 'The selected product does not exist.',
        ];
    }
}
