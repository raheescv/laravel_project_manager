<?php

namespace App\Http\Requests\Product;

use App\Models\ProductOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(ProductOffer::types()))],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(ProductOffer::statuses()))],
            'items' => ['required', 'array', 'min:1', 'max:2000'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the offer a name.',
            'start_date.required' => 'Pick the date the offer starts.',
            'end_date.required' => 'Pick the date the offer ends.',
            'end_date.after_or_equal' => 'The offer must end on or after its start date.',
            'items.required' => 'Add at least one product to the offer.',
            'items.min' => 'Add at least one product to the offer.',
            'items.max' => 'An offer can hold at most 2000 products.',
            'items.*.product_id.distinct' => 'A product can only appear once in an offer.',
            'items.*.amount.required' => 'Every product needs an offer price.',
            'items.*.amount.min' => 'Offer prices cannot be negative.',
        ];
    }
}
