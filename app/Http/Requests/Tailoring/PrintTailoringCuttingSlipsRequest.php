<?php

namespace App\Http\Requests\Tailoring;

use Illuminate\Foundation\Http\FormRequest;

class PrintTailoringCuttingSlipsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one tailoring order to print cutting slips.',
            'ids.array' => 'The selected tailoring orders are invalid.',
            'ids.min' => 'Select at least one tailoring order to print cutting slips.',
            'ids.*.integer' => 'Each selected tailoring order must be valid.',
            'ids.*.distinct' => 'Duplicate tailoring orders cannot be printed together.',
            'ids.*.min' => 'Each selected tailoring order must be valid.',
        ];
    }
}
