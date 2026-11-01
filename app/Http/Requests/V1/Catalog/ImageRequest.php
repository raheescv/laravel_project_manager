<?php

namespace App\Http\Requests\V1\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class ImageRequest extends FormRequest
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
            'image' => ['required', 'image', 'max:8192'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Choose a photo to upload.',
            'image.image' => 'That file is not a photo.',
            'image.max' => 'The photo is larger than 8 MB.',
        ];
    }
}
