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
            // Gratuity collected on the sale — stored as an independent extra amount.
            'tip' => ['nullable', 'numeric', 'min:0'],
            // The payment "mode": a method name (e.g. "Cash"), "credit" (no payment),
            // or "custom" (one or more methods supplied in `payments`).
            'paymentMethod' => ['required', 'string', 'max:50'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
            // Custom payment breakdown — required when paymentMethod is "custom".
            'payments' => ['nullable', 'array', 'required_if:paymentMethod,custom'],
            'payments.*.payment_method_id' => ['required_with:payments', 'integer'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
            'sendToWhatsapp' => ['nullable', 'boolean'],
            // Omitted (or "completed") finalizes the sale; "draft" parks it without
            // touching stock or posting a journal entry.
            'status' => ['nullable', 'string', 'in:draft,completed'],
            // Idempotency key generated on the device when Charge was tapped. A
            // sale queued offline is replayed until the server acknowledges it,
            // so the same uuid may arrive several times — the second and later
            // arrivals return the sale that already exists rather than creating
            // another. No `unique` rule here: a repeat is a legitimate retry,
            // not a validation failure.
            'clientUuid' => ['nullable', 'uuid'],
            // The till's own clock when the sale was rung up. Audit and queue
            // ordering only — never trusted for the accounting date.
            'clientCreatedAt' => ['nullable', 'date'],
            // Who actually took the sale, when it was rung up offline. A shared
            // till may be signed in as a different cashier by the time the queue
            // drains, and the sale belongs to whoever served the customer — not
            // to whoever happened to be standing there later.
            //
            // Only the cashier is claimed. The branch is never sent: it follows
            // that cashier's own assigned branch, which removes any way to post
            // a sale into a branch you have no business in.
            'clientUserId' => ['nullable', 'integer'],
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
