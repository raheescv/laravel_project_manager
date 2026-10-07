<?php

namespace App\Http\Requests\V1\Technician\Checklist;

use App\Enums\RentOut\FixtureStatus;
use Illuminate\Validation\Rule;

class UpdateFixtureRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'comments' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(FixtureStatus::class)],
            'completed_date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fixtureChanges(): array
    {
        return collect($this->validated())->except('phase')->all();
    }
}
