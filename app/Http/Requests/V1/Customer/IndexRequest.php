<?php

namespace App\Http\Requests\V1\Customer;

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
            'mobile' => ['nullable', 'string', 'max:15'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:name,mobile,id'],
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
            'sort_by.in' => 'The sort by field must be one of: name, mobile, id.',
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
        return array_merge([
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 20,
            'page' => 1,
        ], $this->validated());
    }
}
