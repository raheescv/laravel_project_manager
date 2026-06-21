<?php

namespace App\Http\Requests\V1\DaySession;

use App\Models\SaleDaySession;
use Illuminate\Foundation\Http\FormRequest;

class ToggleRequest extends FormRequest
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
        // `now` (not `today`) so a date *with a time-of-day* on the current day
        // passes validation — the mobile app sends a full `Y-m-d H:i:s` datetime.
        $rules = ['required', 'date', 'before_or_equal:now'];

        if ($this->isClosing()) {
            $openedAt = $this->openSession()?->opened_at;
            if ($openedAt) {
                // Compare full datetimes so a close can't land before the open
                // moment (the picker offers a time too).
                $rules[] = 'after_or_equal:'.$openedAt->toDateTimeString();
            }
        }

        return [
            'date' => $rules,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $openedAt = $this->openSession()?->opened_at?->format('d M Y, g:i A');

        return [
            'date.after_or_equal' => $openedAt
                ? 'The closing time must be on or after the opening ('.$openedAt.').'
                : 'The closing time must be on or after the opening time.',
            'date.before_or_equal' => 'The date & time cannot be in the future.',
        ];
    }

    public function isClosing(): bool
    {
        $branchId = $this->branchId();

        return $branchId ? SaleDaySession::hasOpenSession($branchId) : false;
    }

    public function branchId(): ?int
    {
        return $this->user()?->default_branch_id;
    }

    public function openSession(): ?SaleDaySession
    {
        $branchId = $this->branchId();

        return $branchId ? SaleDaySession::getOpenSessionForBranch($branchId) : null;
    }
}
