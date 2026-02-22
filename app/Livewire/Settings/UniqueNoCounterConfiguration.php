<?php

namespace App\Livewire\Settings;

use App\Models\UniqueNoCounter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class UniqueNoCounterConfiguration extends Component
{
    public array $rows = [];

    public function mount(): void
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
        }

        $this->loadRows();
    }

    public function save(): void
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
        }

        $this->validate([
            'rows.*.number' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function (): void {
            foreach ($this->rows as $row) {
                UniqueNoCounter::query()
                    ->where('year', $row['year'])
                    ->where('branch_code', $row['branch_code'])
                    ->where('segment', $row['segment'])
                    ->update(['number' => (int) $row['number']]);
            }
        });

        $this->dispatch('success', ['message' => 'Unique counter values updated successfully']);
        $this->loadRows();
    }

    public function render()
    {
        return view('livewire.settings.unique-no-counter-configuration');
    }

    private function loadRows(): void
    {
        $this->rows = UniqueNoCounter::query()
            ->orderBy('year')
            ->orderBy('branch_code')
            ->orderBy('segment')
            ->get(['year', 'branch_code', 'segment', 'number'])
            ->map(static fn (UniqueNoCounter $counter): array => [
                'year' => (string) $counter->year,
                'branch_code' => (string) $counter->branch_code,
                'segment' => (string) $counter->segment,
                'number' => (int) $counter->number,
            ])
            ->toArray();
    }
}
