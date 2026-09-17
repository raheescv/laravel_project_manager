<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            // The mobile number the school has on file, or the parent's email.
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
            // Keep me signed in: a 30-day token instead of a 12-hour one.
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['login' => 'mobile number or email'];
    }
}
