<?php

namespace App\Http\Requests\ProjectContract;

use App\Enums\ProjectContract\SettlementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettlementRequest extends FormRequest
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
            'type' => ['required', Rule::enum(SettlementType::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method_id' => ['nullable', 'integer'],
            'reason' => [Rule::requiredIf($this->input('type') === SettlementType::Forfeit->value), 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Give a reason for forfeiting the security.',
            'date.before_or_equal' => 'A settlement cannot be dated in the future.',
        ];
    }
}
