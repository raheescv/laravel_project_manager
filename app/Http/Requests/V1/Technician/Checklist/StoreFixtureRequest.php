<?php

namespace App\Http\Requests\V1\Technician\Checklist;

use App\Enums\RentOut\FixtureStatus;
use Illuminate\Validation\Rule;

class StoreFixtureRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'category' => ['required', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::enum(FixtureStatus::class)],
        ];
    }
}
