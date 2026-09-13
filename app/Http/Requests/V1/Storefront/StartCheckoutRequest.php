<?php

namespace App\Http\Requests\V1\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StartCheckoutRequest extends FormRequest
{
    /**
     * Public endpoint: the storefront has no signed-in customer.
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
            // Collect from a shop, or delivered from the store's delivery branch.
            'fulfilment' => ['required', 'string', 'in:pickup,delivery'],
            // The shop to collect from — its stock is what the order draws on.
            'branchId' => ['nullable', 'required_if:fulfilment,pickup', 'integer'],
            'customerName' => ['required', 'string', 'max:100'],
            'customerEmail' => ['required', 'email', 'max:150'],
            // Dialling code without "+", e.g. 974. Kept apart from the number so the
            // customer account stores the local number the shop staff search by.
            'countryCode' => ['required', 'string', 'regex:/^\d{1,4}$/'],
            'customerMobile' => ['required', 'string', 'regex:/^\d{6,15}$/'],
            'address' => ['nullable', 'required_if:fulfilment,delivery', 'string', 'max:500'],
            // Ids and quantities only — prices are always read from the catalogue,
            // never taken from the browser.
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.productId' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9'],
            // The storefront page Tap sends the customer back to.
            'returnUrl' => ['required', 'string', 'url:http,https', 'max:2000'],
        ];
    }
}
