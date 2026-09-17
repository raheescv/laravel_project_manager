<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;

class BlockCardRequest extends FormRequest
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
            // What happened, in the parent's words. Optional.
            'reason' => ['nullable', 'string', 'max:200'],
        ];
    }
}
