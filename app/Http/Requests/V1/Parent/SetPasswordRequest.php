<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;

class SetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The one-time token from the invite / reset link.
            'token' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ];
    }
}
