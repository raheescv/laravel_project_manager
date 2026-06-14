<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Resolve which login method this request uses.
     *
     * Defaults to "pin". If an explicit `method` isn't given, it is inferred as
     * "password" when a username/password is present, otherwise "pin".
     */
    public function resolvedMethod(): string
    {
        $method = $this->input('method');

        if (in_array($method, ['pin', 'password'], true)) {
            return $method;
        }

        return ($this->filled('username') || $this->filled('password')) ? 'password' : 'pin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->resolvedMethod() === 'password') {
            return [
                'method' => ['nullable', 'string', 'in:pin,password'],
                'username' => ['required', 'string', 'max:100'],
                'password' => ['required', 'string', 'max:100'],
            ];
        }

        return [
            'method' => ['nullable', 'string', 'in:pin,password'],
            'pin' => ['required', 'string', 'max:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'The PIN is required.',
            'pin.max' => 'The PIN must be less than 6 digits.',
            'username.required' => 'The username (email, code or mobile) is required.',
            'password.required' => 'The password is required.',
        ];
    }
}
