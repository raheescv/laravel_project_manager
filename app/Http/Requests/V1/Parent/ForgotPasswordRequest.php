<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            // The mobile number or the email the school has for the parent.
            'login' => ['required_without:mobile', 'nullable', 'string', 'max:150'],
            // Portal builds from before email was accepted send the number here.
            'mobile' => ['required_without:login', 'nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return ['login' => 'mobile number or email', 'mobile' => 'mobile number'];
    }
}
