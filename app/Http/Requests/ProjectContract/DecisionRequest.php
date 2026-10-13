<?php

namespace App\Http\Requests\ProjectContract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecisionRequest extends FormRequest
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
            'subject' => ['required', Rule::in(['contract', 'extension', 'settlement'])],
            'subject_id' => ['required', 'integer'],
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'comment' => [Rule::requiredIf($this->input('decision') === 'reject'), 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comment.required' => 'Tell the submitter why it was rejected.',
        ];
    }
}
