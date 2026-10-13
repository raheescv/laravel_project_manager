<?php

namespace App\Http\Requests\ProjectContract;

use App\Enums\ProjectContract\PaymentFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
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
            'project_name' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'no_of_terms' => ['required', 'integer', 'min:1', 'max:999'],
            'payment_frequency' => ['required', Rule::enum(PaymentFrequency::class)],
            'contract_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'remark' => ['nullable', 'string', 'max:5000'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_name.required' => 'Enter the project name.',
            'account_id.required' => 'Choose the vendor.',
            'start_date.required' => 'Pick the start date.',
            'end_date.required' => 'Pick the end date.',
            'end_date.after' => 'The end date must be after the start date.',
            'no_of_terms.min' => 'Plan at least one term.',
            'payment_frequency.required' => 'Choose a payment frequency.',
            'contract_amount.required' => 'Enter the contract amount.',
            'contract_amount.gt' => 'The contract amount must be above zero.',
        ];
    }
}
