<?php

namespace App\Http\Requests\V1\Day;

use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
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
            'openDate' => ['required', 'date'],
            'closingDate' => ['required', 'date', 'after_or_equal:openDate'],
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
            'closingDate.after_or_equal' => 'The closing date must be on or after the open date.',
        ];
    }
}
