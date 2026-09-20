<?php

namespace App\Livewire\Student;

use App\Models\Branch;
use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;

/** Student view → Purchases: the bills raised against the student, with the period's totals. */
class Purchases extends Component
{
    use WithPagination;

    public $account_id;

    public $from_date;

    public $to_date;

    /** The quick period button that matches the dates, or null once the dates are typed by hand. */
    public $preset = 'month';

    public $sortField = 'date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    /** Sortable columns, so a crafted sortBy() cannot reach the query. */
    private const SORTABLE = ['date', 'invoice_no', 'branch', 'items', 'status', 'grand_total'];

    public function mount($account_id)
    {
        $this->account_id = $account_id;
        $this->applyPreset('month');
    }

    public function applyPreset($preset)
    {
        [$from, $to] = match ($preset) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'three_months' => [now()->subMonthsNoOverflow(2)->startOfMonth(), now()],
            'all' => [null, null],
            default => [now()->startOfMonth(), now()],
        };

        $this->preset = in_array($preset, ['last_month', 'three_months', 'all'], true) ? $preset : 'month';
        $this->from_date = $from?->toDateString();
        $this->to_date = $to?->toDateString();
        $this->resetPage('purchases_page');
    }

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            // Money and dates read best biggest/newest first; names and statuses A-Z.
            $this->sortDirection = in_array($field, ['date', 'grand_total', 'items'], true) ? 'desc' : 'asc';
        }
        $this->resetPage('purchases_page');
    }

    public function updatedFromDate()
    {
        $this->preset = null;
        $this->resetPage('purchases_page');
    }

    public function updatedToDate()
    {
        $this->preset = null;
        $this->resetPage('purchases_page');
    }

    protected function baseQuery()
    {
        return Sale::where('account_id', $this->account_id)
            ->when($this->from_date, fn ($q, $value) => $q->where('date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date, fn ($q, $value) => $q->where('date', '<=', date('Y-m-d', strtotime($value))));
    }

    /** Order the bills by the column the user clicked, always breaking ties on the bill's own order. */
    protected function sorted($query)
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortField) {
            'branch' => $query->orderBy(Branch::select('name')->whereColumn('branches.id', 'sales.branch_id'), $direction),
            'items' => $query->orderBy('items_count', $direction),
            'invoice_no', 'status', 'grand_total' => $query->orderBy($this->sortField, $direction),
            default => $query->orderBy('date', $direction),
        };

        return $query->orderBy('id', $this->sortField === 'date' ? $direction : 'desc');
    }

    public function render()
    {
        $totals = $this->baseQuery()
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) AS bills, COALESCE(SUM(grand_total), 0) AS total')
            ->first();

        $topBranchId = $this->baseQuery()
            ->where('status', 'completed')
            ->selectRaw('branch_id, COUNT(*) AS bills')
            ->groupBy('branch_id')
            ->orderByDesc('bills')
            ->first();

        $sales = $this->sorted(
            $this->baseQuery()
                ->with(['branch:id,name', 'payments:id,sale_id,payment_method_id', 'payments.paymentMethod:id,name'])
                ->withCount('items')
        )->paginate(15, ['id', 'date', 'invoice_no', 'branch_id', 'status', 'grand_total'], 'purchases_page');

        return view('livewire.student.purchases', [
            'sales' => $sales,
            'bills' => (int) ($totals->bills ?? 0),
            'total' => (float) ($totals->total ?? 0),
            'topBranch' => $topBranchId ? Branch::find($topBranchId->branch_id)?->name : null,
            'topBranchBills' => (int) ($topBranchId->bills ?? 0),
        ]);
    }
}
