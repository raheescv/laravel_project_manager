<?php

namespace App\Livewire\SaleDaySession;

use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\SalePayment;
use Livewire\Component;
use Livewire\WithPagination;

class DaySessionSalesList extends Component
{
    use WithPagination;

    public $sessionId;

    public $session;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['refreshSalesList' => '$refresh'];

    public function mount($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->loadSession();
    }

    public function loadSession()
    {
        $this->session = SaleDaySession::with(['branch', 'opener', 'closer'])->find($this->sessionId);
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
        $baseQuery = Sale::completed()->with(['account', 'payments.paymentMethod'])
            ->where('sale_day_session_id', $this->sessionId);

        if ($this->search) {
            $baseQuery->where(function ($q): void {
                $search = '%'.$this->search.'%';
                $q->where('invoice_no', 'like', $search)
                    ->orWhere('customer_name', 'like', $search)
                    ->orWhere('customer_mobile', 'like', $search)
                    ->orWhere('id', 'like', $search);
            });
        }

        $totals = Sale::completed()->where('sale_day_session_id', $this->sessionId)
            ->selectRaw('
                SUM(total) as total,
                SUM(item_discount) as item_discount,
                SUM(tax_amount) as tax_amount,
                SUM(paid) as paid,
                SUM(balance) as balance,
                COUNT(*) as total_count
            ')
            ->first()->toArray();

        // Get payment method summary from SalePayment table
        $paymentSummary = SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('accounts', 'sale_payments.payment_method_id', '=', 'accounts.id')
            ->whereDate('sale_payments.date', date('Y-m-d', strtotime($this->session->opened_at)))
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->selectRaw('
                accounts.name as payment_method_name,
                COUNT(DISTINCT sales.id) as count,
                SUM(sale_payments.amount) as total_paid
            ')
            ->groupBy('accounts.id', 'accounts.name')
            ->get()
            ->toArray();

        $sales = $baseQuery
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.sale-day-session.day-session-sales-list', [
            'sales' => $sales,
            'totals' => $totals,
            'paymentSummary' => $paymentSummary,
        ]);
    }
}
