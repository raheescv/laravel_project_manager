<?php

namespace App\Http\Requests\V1\Technician\Checklist;

class PhotoRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'photo' => $this->photoRules(),
        ];
    }
}
