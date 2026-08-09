<?php

namespace App\Http\Requests\V1\DaySession;

use App\Models\SaleDaySession;
use Carbon\Carbon;
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
     * Normalise the submitted moment into the application timezone.
     *
     * The mobile app stamps the time from the *device's* clock. If the phone sits
     * in a different timezone from the server, its "now" is not the server's now —
     * far enough ahead and closing the day fails as a future date. So accept an
     * absolute instant (ISO-8601 carrying `Z` or a `+05:30` style offset) and
     * convert it here. A bare `Y-m-d H:i:s` from an older build has no offset to
     * honour, so Carbon reads it as app-timezone wall clock exactly as before.
     */
    protected function prepareForValidation(): void
    {
        $date = $this->input('date');

        if (! is_string($date) || trim($date) === '') {
            return;
        }

        try {
            $this->merge([
                'date' => Carbon::parse($date)
                    ->setTimezone(config('app.timezone'))
                    ->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Unparseable — leave it untouched so the `date` rule reports it.
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // The session moment can't be in the future. Use `now` (not `today`) so a
        // date *with a time-of-day* on the current day passes — the mobile app
        // sends a full `Y-m-d H:i:s`. Add a small buffer so submitting the current
        // time doesn't fail on the second or two of latency + clock skew between
        // the phone stamping the time and the server validating it.
        $rules = ['required', 'date', 'before_or_equal:'.now()->addMinutes(5)->toDateTimeString()];

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
