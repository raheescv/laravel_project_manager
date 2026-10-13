<?php

namespace App\Http\Requests\ProjectContract;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'term_ids' => ['required', 'array', 'min:1'],
            'term_ids.*' => ['integer', 'distinct'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method_id' => ['required', 'integer'],
            'cheque_no' => ['nullable', 'string', 'max:50'],
            'cheque_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'term_ids.required' => 'Choose at least one term to pay.',
            'date.before_or_equal' => 'A payment cannot be dated in the future.',
            'amount.gt' => 'Enter an amount above zero.',
            'payment_method_id.required' => 'Choose a payment method.',
        ];
    }
}
