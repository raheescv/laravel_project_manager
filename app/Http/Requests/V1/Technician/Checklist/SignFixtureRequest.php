<?php

namespace App\Http\Requests\V1\Technician\Checklist;

class SignFixtureRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'signature' => $this->signatureRules(),
        ];
    }
}
