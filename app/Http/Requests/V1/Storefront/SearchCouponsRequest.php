<?php

namespace App\Http\Requests\V1\Storefront;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class SearchCouponsRequest extends FormRequest
{
    /**
     * Public endpoint: the storefront has no signed-in customer.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = Coupon::normalizeCode($this->input('q'));

        $this->merge(['q' => $code === '' ? null : $code]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No q lists the open codes. A search needs at least 3 characters, so
            // nobody can walk the private codes letter by letter.
            'q' => ['nullable', 'string', 'min:3', 'max:24', 'regex:/^[A-Z0-9-]+$/'],
            ...StartCheckoutRequest::ITEM_RULES,
        ];
    }
}
