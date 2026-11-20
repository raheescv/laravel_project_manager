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

    protected function prepareForValidation(): void
    {
        $reference = $this->route('id');
        if ($reference === null) {
            return;
        }

        // The showcase links by name ("nike-air-max-90"); the apps and old links send the id.
        $this->merge(['link' => $reference]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required_without_all:barcode,link', 'nullable', 'integer', 'exists:products,id'],
            'barcode' => ['required_without_all:id,link', 'nullable', 'string', 'exists:products,barcode'],
            'link' => ['nullable', 'string', 'max:255'],
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
            'id.required_without_all' => 'Either Product ID or Barcode is required.',
            'barcode.required_without_all' => 'Either Product ID or Barcode is required.',
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
