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
        $rules = ['required', 'date', 'before_or_equal:today'];

        if ($this->isClosing()) {
            $openedAt = $this->openSession()?->opened_at;
            if ($openedAt) {
                $rules[] = 'after_or_equal:'.$openedAt->toDateString();
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
        $openedDate = $this->openSession()?->opened_at?->toDateString();

        return [
            'date.after_or_equal' => $openedDate
                ? 'The date must be on or after '.$openedDate.'.'
                : 'The date must be on or after the opening date.',
            'date.before_or_equal' => 'The date cannot be in the future.',
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
