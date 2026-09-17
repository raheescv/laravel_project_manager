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

    protected $paginationTheme = 'bootstrap';

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

        $sales = $this->baseQuery()
            ->with(['branch:id,name', 'payments:id,sale_id,payment_method_id', 'payments.paymentMethod:id,name'])
            ->withCount('items')
            ->latest('date')
            ->latest('id')
            ->paginate(15, ['id', 'date', 'invoice_no', 'branch_id', 'status', 'grand_total'], 'purchases_page');

        return view('livewire.student.purchases', [
            'sales' => $sales,
            'bills' => (int) ($totals->bills ?? 0),
            'total' => (float) ($totals->total ?? 0),
            'topBranch' => $topBranchId ? Branch::find($topBranchId->branch_id)?->name : null,
            'topBranchBills' => (int) ($topBranchId->bills ?? 0),
        ]);
    }
}
