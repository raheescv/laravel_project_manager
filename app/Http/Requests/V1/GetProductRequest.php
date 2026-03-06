<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class GetProductRequest extends FormRequest
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
            'id' => ['required_without:barcode', 'nullable', 'integer', 'exists:products,id'],
            'barcode' => ['required_without:id', 'nullable', 'string', 'exists:products,barcode'],
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
            'id.required_without' => 'Either Product ID or Barcode is required.',
            'barcode.required_without' => 'Either Product ID or Barcode is required.',
        ];
    }

    /**
     * Get the validated data.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        return $this->validated();
    }
}
