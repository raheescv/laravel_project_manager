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
            // The payment "mode": a method name (e.g. "Cash"), "credit" (no payment),
            // or "custom" (one or more methods supplied in `payments`).
            'paymentMethod' => ['required', 'string', 'max:50'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
            // Custom payment breakdown — required when paymentMethod is "custom".
            'payments' => ['nullable', 'array', 'required_if:paymentMethod,custom'],
            'payments.*.payment_method_id' => ['required_with:payments', 'integer'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
            'sendToWhatsapp' => ['nullable', 'boolean'],
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
            'payments.required_if' => 'At least one payment is required for a custom payment.',
        ];
    }
}
