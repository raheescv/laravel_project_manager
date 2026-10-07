<?php

namespace App\Http\Requests\V1\Technician\Checklist;

class FixturePhotoRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'which' => ['required', 'string', 'in:before,after'],
            'photo' => $this->photoRules(),
        ];
    }
}
