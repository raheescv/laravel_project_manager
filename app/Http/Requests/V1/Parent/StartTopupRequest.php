<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;

class StartTopupRequest extends FormRequest
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
            // QAR. The school's limits (Settings → Student Settings) are checked by the action.
            'amount' => ['required', 'numeric', 'gt:0'],
            // Language of QPay's payment page.
            'lang' => ['nullable', 'string', 'in:En,Ar'],
        ];
    }
}
