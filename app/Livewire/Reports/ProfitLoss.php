<?php

namespace App\Livewire\Reports;

use App\Exports\ProfitLossExport;
use App\Models\Branch;
use App\Services\ProfitLossReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLoss extends Component
{
    public string $start_date = '';

    public string $end_date = '';

    public $branch_id;

    /** @var array<int, string> */
    public array $branches = [];

    public string $period = 'monthly';

    /** @var list<int> */
    public array $expandedGroups = [];

    public function mount(): void
    {
        $this->applyPeriod($this->period);

        $branchIds = Auth::user()->branches->pluck('branch_id')->all();
        $this->branches = Branch::whereIn('id', $branchIds)->pluck('name', 'id')->toArray();
        $this->branch_id = session('branch_id');
    }

    public function updatedPeriod(string $value): void
    {
        $this->applyPeriod($value);
    }

    public function toggleGroup(int $groupId): void
    {
        $this->expandedGroups = in_array($groupId, $this->expandedGroups, true)
            ? array_values(array_diff($this->expandedGroups, [$groupId]))
            : [...$this->expandedGroups, $groupId];
    }

    public function fetchData(): void
    {
        // Triggers re-render with the current filter values.
    }

    public function resetFilters(): void
    {
        $this->period = 'monthly';
        $this->applyPeriod($this->period);
        $this->branch_id = '';
        $this->expandedGroups = [];
    }

    public function export()
    {
        try {
            $reportData = $this->reportService()->build();
            $branchName = $this->branch_id ? Branch::find($this->branch_id)?->name : null;
            $fileName = 'Profit_Loss_Report_'.$this->start_date.'_to_'.$this->end_date.'_'.now()->format('Y-m-d_H-i-s').'.xlsx';

            return Excel::download(
                new ProfitLossExport($reportData, $this->start_date, $this->end_date, $branchName),
                $fileName
            );
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.reports.profit-loss', [
            ...$this->reportService()->build(),
            'branches' => $this->branches,
        ]);
    }

    private function reportService(): ProfitLossReportService
    {
        return new ProfitLossReportService(
            startDate: $this->start_date,
            endDate: $this->end_date,
            branchId: $this->branch_id ? (int) $this->branch_id : null,
        );
    }

    /**
     * Sync start/end date with the currently selected period preset.
     */
    private function applyPeriod(string $period): void
    {
        [$start, $end] = $this->periodRange($period);
        $this->start_date = $start->format('Y-m-d');
        $this->end_date = $end->format('Y-m-d');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'quarterly' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'previous_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
