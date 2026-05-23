<?php

namespace App\Http\Requests\V1\Sale;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'customerName' => ['required', 'string', 'max:100'],
            'phoneNumber' => ['nullable', 'string', 'max:15'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'integer', 'exists:products,id'],
            'items.*.employeeId' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unitPrice' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paymentMethod' => ['required', 'string', 'max:50'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
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
            'items.required' => 'At least one sale item is required.',
            'items.min' => 'At least one sale item is required.',
        ];
    }
}
