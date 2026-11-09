<?php

namespace App\Http\Requests\V1\Parent;

use App\Support\Student\StudentSettings;
use Illuminate\Foundation\Http\FormRequest;

/** How many school days of a student's pre-orders to return. */
class PreOrderScheduleRequest extends FormRequest
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
            'days' => ['nullable', 'integer', 'min:1', 'max:'.(StudentSettings::PRE_ORDER_DAYS_AHEAD + 1)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'days.max' => 'Meals can only be shown for the days that can be ordered.',
        ];
    }
}
