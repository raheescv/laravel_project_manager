<?php

namespace App\Livewire\RentOut\Report;

use App\Actions\RentOut\Report\GetCommissionRowsAction;
use App\Enums\RentOut\CommissionStatus;
use App\Exports\RentOut\CommissionExport;
use Carbon\Carbon;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Commission Report: Agent, Property, Contract No., Tenant, %, Amount and
 * Paid/Unpaid, grouped by salesman with subtotals. Drafts and rejected
 * commissions are left out — they are not owed.
 */
class CommissionReport extends Component
{
    public $dateFrom = '';

    public $dateTo = '';

    public $agentId = '';

    /** '', 'paid', 'unpaid' */
    public $paid = '';

    public $search = '';

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfYear()->format('Y-m-d');
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'reportable' => true,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'agentId' => $this->agentId,
            'paid' => $this->paid,
            'search' => $this->search,
        ];
    }

    public function download()
    {
        abort_unless(auth()->user()?->can('rent out commission.report'), 403);

        return Excel::download(new CommissionExport($this->filters()), 'commission-report-'.now()->format('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $groups = (new GetCommissionRowsAction())->groupedByAgent($this->filters());
        $rows = $groups->flatten(1);
        $paid = (float) $rows->filter(fn ($commission) => $commission->status === CommissionStatus::Paid)->sum('amount');
        $total = (float) $rows->sum('amount');

        return view('livewire.rent-out.report.commission-report', [
            'groups' => $groups,
            'totals' => ['total' => $total, 'paid' => $paid, 'unpaid' => round($total - $paid, 2), 'count' => $rows->count()],
        ]);
    }
}
