<?php

namespace App\Http\Requests\V1\Technician\Checklist;

class SealRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'remarks' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'actual_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
