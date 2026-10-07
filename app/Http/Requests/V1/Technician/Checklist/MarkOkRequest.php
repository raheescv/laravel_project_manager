<?php

namespace App\Http\Requests\V1\Technician\Checklist;

class MarkOkRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'line_ids' => ['required', 'array', 'min:1'],
            'line_ids.*' => ['integer'],
        ];
    }
}
