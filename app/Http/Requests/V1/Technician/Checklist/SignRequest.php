<?php

namespace App\Http\Requests\V1\Technician\Checklist;

use App\Enums\RentOut\ChecklistSignatoryRole;
use Illuminate\Validation\Rule;

class SignRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'role' => ['required', Rule::enum(ChecklistSignatoryRole::class)],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signature' => $this->signatureRules(),
        ];
    }

    public function role(): ChecklistSignatoryRole
    {
        return ChecklistSignatoryRole::from($this->validated('role'));
    }
}
