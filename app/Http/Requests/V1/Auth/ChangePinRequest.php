<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePinRequest extends FormRequest
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
            'current_pin' => ['required', 'string', 'max:6'],
            'new_pin' => ['required', 'string', 'max:6', 'different:current_pin', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_pin.required' => 'The current PIN is required.',
            'current_pin.max' => 'The current PIN must be less than 6 digits.',
            'new_pin.required' => 'The new PIN is required.',
            'new_pin.max' => 'The new PIN must be less than 6 digits.',
            'new_pin.different' => 'The new PIN must be different from the current PIN.',
            'new_pin.confirmed' => 'The new PIN confirmation does not match.',
        ];
    }
}
