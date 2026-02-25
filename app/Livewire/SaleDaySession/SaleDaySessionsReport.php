<?php

namespace App\Livewire\SaleDaySession;

use App\Models\SaleDaySession;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;

class SaleDaySessionsReport extends Component
{
    use WithPagination;

    public $branchId;

    public $dateFrom;

    public $dateTo;

    public $status;

    public $sortField = 'opened_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = date('Y-m-d', strtotime('-30 days'));
        $this->dateTo = date('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $sessions = SaleDaySession::with(['branch', 'opener', 'closer'])
            ->when($this->branchId, function ($q) {
                return $q->where('branch_id', $this->branchId);
            })
            ->when($this->dateFrom, function ($q) {
                return $q->whereDate('opened_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                return $q->whereDate('opened_at', '<=', $this->dateTo);
            })
            ->when($this->status, function ($q) {
                return $q->where('status', $this->status);
            })
            ->withCount('sales')
            ->withCount('tailoringOrders')
            ->withSum('sales', 'paid')
            ->withSum('tailoringOrders', 'paid')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Get summary data
        $summary = [
            'total_sessions' => SaleDaySession::when($this->branchId, function ($q) {
                return $q->where('branch_id', $this->branchId);
            })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->count(),

            'open_sessions' => SaleDaySession::where('status', 'open')
                ->when($this->branchId, function ($q) {
                    return $q->where('branch_id', $this->branchId);
                })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->count(),

            'closed_sessions' => SaleDaySession::where('status', 'closed')
                ->when($this->branchId, function ($q) {
                    return $q->where('branch_id', $this->branchId);
                })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->count(),

            'total_sales' => SaleDaySession::when($this->branchId, function ($q) {
                return $q->where('branch_id', $this->branchId);
            })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->withCount('sales')
                ->get()
                ->sum('sales_count'),

            'total_sales_amount' => SaleDaySession::when($this->branchId, function ($q) {
                return $q->where('branch_id', $this->branchId);
            })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->withSum('sales', 'paid')
                ->get()
                ->sum('sales_sum_paid'),

            'total_tailoring' => SaleDaySession::when($this->branchId, function ($q) {
                return $q->where('branch_id', $this->branchId);
            })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->withCount('tailoringOrders')
                ->get()
                ->sum('tailoring_orders_count'),

            'total_tailoring_amount' => SaleDaySession::when($this->branchId, function ($q) {
                return $q->where('branch_id', $this->branchId);
            })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->withSum('tailoringOrders', 'paid')
                ->get()
                ->sum('tailoring_orders_sum_paid'),

            'total_difference' => SaleDaySession::where('status', 'closed')
                ->when($this->branchId, function ($q) {
                    return $q->where('branch_id', $this->branchId);
                })
                ->when($this->dateFrom, function ($q) {
                    return $q->whereDate('opened_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->whereDate('opened_at', '<=', $this->dateTo);
                })
                ->sum('difference_amount'),
        ];

        $summary['total_invoices'] = (int) $summary['total_sales'] + (int) $summary['total_tailoring'];
        $summary['total_collection_amount'] = (float) $summary['total_sales_amount'] + (float) $summary['total_tailoring_amount'];

        $branches = Branch::orderBy('name')->get();

        return view('livewire.sale-day-session.sale-day-sessions-report', [
            'sessions' => $sessions,
            'branches' => $branches,
            'summary' => $summary,
        ]);
    }
}
