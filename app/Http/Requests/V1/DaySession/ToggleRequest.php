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
        if ($this->isClosing()) {
            $openSession = $this->openSession();
            $rules = ['required', 'date', 'before_or_equal:today'];

            if ($openSession?->opened_at) {
                $rules[] = 'after_or_equal:'.$openSession->opened_at->toDateString();
            }

            return [
                'closingDate' => $rules,
            ];
        }

        return [
            'openDate' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $openedDate = $this->openSession()?->opened_at?->toDateString();

        return [
            'closingDate.after_or_equal' => $openedDate
                ? 'The closing date must be on or after '.$openedDate.'.'
                : 'The closing date must be on or after the opening date.',
            'closingDate.before_or_equal' => 'The closing date cannot be in the future.',
            'openDate.before_or_equal' => 'The opening date cannot be in the future.',
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
