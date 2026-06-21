<?php

namespace App\Http\Requests\V1\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
     * Mirrors SaleReturn\StoreRequest, but every line may carry an `id` (the
     * existing sale_return_item id). Lines with an id update that row; lines
     * without one are added. Any existing line whose id is absent is removed.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'date' => ['nullable', 'date'],
            'other_discount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:sale_return_items,id'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unitPrice' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            // The refund "mode": a method name (e.g. "Cash"), "credit" (no refund
            // recorded), or "custom" (one or more methods supplied in `payments`).
            'paymentMethod' => ['required', 'string', 'max:50'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
            // Custom refund breakdown — required when paymentMethod is "custom".
            'payments' => ['nullable', 'array', 'required_if:paymentMethod,custom'],
            'payments.*.payment_method_id' => ['required_with:payments', 'integer'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
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
            'sale_id.required' => 'A return must be raised against a sale.',
            'items.required' => 'At least one return item is required.',
            'items.min' => 'At least one return item is required.',
            'payments.required_if' => 'At least one payment is required for a custom refund.',
        ];
    }
}
