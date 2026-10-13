<?php

namespace App\Http\Requests\ProjectContract;

use App\Enums\ProjectContract\SecurityDirection;
use App\Enums\ProjectContract\SecurityForm;
use App\Enums\ProjectContract\SecurityPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SecurityRequest extends FormRequest
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
        $guarantee = $this->input('form') === SecurityForm::BankGuarantee->value;

        return [
            'direction' => ['required', Rule::enum(SecurityDirection::class)],
            'form' => ['required', Rule::enum(SecurityForm::class)],
            'purpose' => ['required', Rule::enum(SecurityPurpose::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method_id' => ['nullable', 'integer'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => [$guarantee ? 'required' : 'nullable', 'date', 'after_or_equal:valid_from'],
            'cheque_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'renewed_from_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.gt' => 'Enter an amount above zero.',
            'valid_until.required' => 'Enter the date the guarantee is valid until.',
            'valid_until.after_or_equal' => 'The guarantee must end on or after it starts.',
            'document.max' => 'The scan must be 10 MB or smaller.',
        ];
    }
}
