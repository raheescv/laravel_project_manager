<?php

namespace App\Livewire\Settings;

use App\Models\Designation;
use App\Support\LeadOptions;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Settings → Lead Settings: which designations a lead can be assigned to.
 * The lead form's "Assigned to" picker lists only employees holding one of
 * them; with none chosen every employee is offered, as before.
 *
 * A tap toggles a designation and saves at once.
 */
class LeadAssigneeDesignations extends Component
{
    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function toggle(int $designationId): void
    {
        $this->authorizeAccess();
        abort_unless(Designation::whereKey($designationId)->exists(), 404);

        $selected = LeadOptions::assigneeDesignationIds();
        $selected = in_array($designationId, $selected, true)
            ? array_values(array_diff($selected, [$designationId]))
            : [...$selected, $designationId];

        LeadOptions::save(LeadOptions::ASSIGNEE_DESIGNATIONS, $selected);
    }

    public function clear(): void
    {
        $this->authorizeAccess();
        LeadOptions::save(LeadOptions::ASSIGNEE_DESIGNATIONS, []);
    }

    public function render(): View
    {
        return view('livewire.settings.lead-assignee-designations', [
            'designations' => Designation::query()
                ->withCount(['employees' => fn ($query) => $query->where('type', 'employee')->where('is_active', true)])
                ->orderBy('order_no')
                ->orderBy('name')
                ->get(['id', 'name']),
            'selected' => LeadOptions::assigneeDesignationIds(),
        ]);
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);
    }
}
