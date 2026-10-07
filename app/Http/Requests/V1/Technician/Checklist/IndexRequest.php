<?php

namespace App\Http\Requests\V1\Technician\Checklist;

use App\Actions\V1\Technician\Checklist\ListAction;
use App\Enums\RentOut\ChecklistPhase;
use Illuminate\Validation\Rule;

class IndexRequest extends ChecklistRequest
{
    protected bool $phaseRequired = false;

    protected function checklistRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([ListAction::STATUS_OPEN, ListAction::STATUS_COMPLETED, ListAction::STATUS_ALL])],
            'date_basis' => ['nullable', Rule::enum(ChecklistPhase::class)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }

    /**
     * The list filters. The date basis names the hand-over the range applies to,
     * so it narrows the list to that phase just as `phase` does.
     *
     * @return array{search: ?string, phase: ?ChecklistPhase, status: string, from_date: ?string, to_date: ?string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'phase' => ChecklistPhase::tryFrom((string) $this->validated('date_basis')) ?? $this->phase(),
            'status' => $this->validated('status') ?? ListAction::STATUS_OPEN,
            'from_date' => $this->validated('from_date'),
            'to_date' => $this->validated('to_date'),
        ];
    }
}
