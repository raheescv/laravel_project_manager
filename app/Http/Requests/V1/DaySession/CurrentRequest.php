<?php

namespace App\Http\Requests\V1\DaySession;

/**
 * Which day session a Sale Bill Report is printed for. The branch comes from
 * [StatusRequest]; the optional `start_date` / `end_date` are the Reports
 * screen's date filter, so the report follows the range on screen.
 */
class CurrentRequest extends StatusRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => $this->branchRules(),
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'start_date.date_format' => 'The start date must be a Y-m-d date.',
            'end_date.date_format' => 'The end date must be a Y-m-d date.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ]);
    }
}
