<?php

namespace App\Http\Requests\V1\Parent;

use App\Models\StudentPreOrder;
use Illuminate\Foundation\Http\FormRequest;

class SavePreOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:'.StudentPreOrder::MAX_LINES],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.StudentPreOrder::MAX_QUANTITY],
            // Weekly order only: ISO weekdays (1 = Monday … 7 = Sunday). Checked against the school days by the action.
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            // For the canteen, e.g. "No mayonnaise". Optional.
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one item to the order.',
            'items.min' => 'Add at least one item to the order.',
            'items.*.quantity.max' => 'You can order up to '.StudentPreOrder::MAX_QUANTITY.' of one item.',
        ];
    }
}
