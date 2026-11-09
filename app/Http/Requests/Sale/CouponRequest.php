<?php

namespace App\Http\Requests\Sale;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => Coupon::normalizeCode($this->input('code'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Coupon::rules((int) $this->route('id'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Enter a code.',
            'code.min' => 'A code needs at least 3 characters.',
            'code.regex' => 'Use only letters, numbers and dashes.',
            'code.unique' => 'This code already exists.',
            'title.required' => 'Give the coupon a title.',
            'discount_value.required' => 'Enter the discount.',
            'discount_value.gt' => 'The discount must be above zero.',
            'discount_value.max' => 'A percent discount must be below 100.',
            'applies_to_ids.required' => 'Pick at least one.',
            'applies_to_ids.min' => 'Pick at least one.',
            'starts_on.required' => 'Pick the date it starts.',
            'ends_on.after_or_equal' => 'It must end on or after the start date.',
        ];
    }
}
