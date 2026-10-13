<?php

namespace App\Http\Requests\ProjectContract;

use Illuminate\Foundation\Http\FormRequest;

class ExtensionRequest extends FormRequest
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
            'new_end_date' => ['required', 'date'],
            'additional_amount' => ['nullable', 'numeric', 'min:0'],
            'additional_terms' => ['nullable', 'integer', 'min:0', 'max:999'],
            'reason' => ['required', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_end_date.required' => 'Pick the new end date.',
            'reason.required' => 'Say what changed and who approved it.',
            'document.max' => 'The addendum must be 10 MB or smaller.',
        ];
    }
}
