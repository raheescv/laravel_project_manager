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
            'amount' => ['required_without:pre_order_date', 'nullable', 'numeric', 'gt:0'],
            // Pay for this day's pre-order instead: the amount is the order's own total, worked out by the server.
            'pre_order_date' => ['nullable', 'date_format:Y-m-d'],
            // Language of the payment page.
            'lang' => ['nullable', 'string', 'in:En,Ar'],
            // debit → Qatar debit card through QPay (the default); credit → credit card through the Mastercard Gateway.
            'method' => ['nullable', 'string', 'in:debit,credit'],
            // Credit card only: the parent confirmed they did not pay the debit card top-up that is still being confirmed.
            'confirm_pending' => ['nullable', 'boolean'],
        ];
    }
}
