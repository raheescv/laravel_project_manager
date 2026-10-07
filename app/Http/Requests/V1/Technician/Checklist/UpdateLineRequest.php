<?php

namespace App\Http\Requests\V1\Technician\Checklist;

class UpdateLineRequest extends ChecklistRequest
{
    protected function checklistRules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', 'in:ok,not_ok'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:255'],
            'qty' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'damage_cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * Only the fields the client actually sent, so an omitted field is left alone.
     *
     * @return array<string, mixed>
     */
    public function lineChanges(): array
    {
        return collect($this->validated())->except('phase')->all();
    }
}
