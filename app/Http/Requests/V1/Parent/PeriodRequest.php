<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/** A calendar month of a student's bills or statement. */
class PeriodRequest extends FormRequest
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
            // YYYY-MM. Defaults to the current month.
            'month' => ['nullable', 'date_format:Y-m'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{0: string, 1: string} first and last day of the month */
    public function period(): array
    {
        $month = $this->validated('month') ?: now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }
}
