<?php

namespace App\Http\Requests\V1\Technician\Checklist;

use App\Enums\RentOut\ChecklistPhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base for the hand-over checklist requests: every write names the phase it works
 * on, and the response comes back for that same phase. Authorisation is the
 * coordinator scoping inside the actions.
 */
abstract class ChecklistRequest extends FormRequest
{
    /** Whether the request must name a phase (writes) or may leave it to the server (reads). */
    protected bool $phaseRequired = true;

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
            'phase' => [$this->phaseRequired ? 'required' : 'nullable', Rule::enum(ChecklistPhase::class)],
            ...$this->checklistRules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function checklistRules(): array
    {
        return [];
    }

    public function phase(): ?ChecklistPhase
    {
        return ChecklistPhase::tryFrom((string) $this->validated('phase'));
    }

    /** The PNG data URI a signature pad hands over. */
    protected function signatureRules(): array
    {
        // A regex rather than starts_with: that rule splits its parameters on commas.
        return ['required', 'string', 'regex:/^data:image\/png;base64,/', 'max:4000000'];
    }

    /** A photo straight off a phone camera. */
    protected function photoRules(): array
    {
        return ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:8192'];
    }
}
