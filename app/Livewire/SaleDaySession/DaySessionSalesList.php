<?php

namespace App\Livewire\SaleDaySession;

use App\Models\SaleDaySession;
use App\Models\SalePayment;
use App\Models\TailoringPayment;
use App\Services\SaleDaySessionDataService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DaySessionSalesList extends Component
{
    use WithPagination;

    private const TOTALS_SELECT = '
        COALESCE(SUM(total), 0) as total,
        COALESCE(SUM(item_discount), 0) as item_discount,
        COALESCE(SUM(tax_amount), 0) as tax_amount,
        COALESCE(SUM(paid), 0) as paid,
        COALESCE(SUM(balance), 0) as balance,
        COUNT(*) as total_count
    ';

    private const SALES_SORTABLE_FIELDS = [
        'id',
        'invoice_no',
        'account_id',
        'date',
        'total',
        'item_discount',
        'tax_amount',
        'payment_method_name',
        'paid',
        'balance',
        'created_at',
    ];

    private const TAILORING_SORTABLE_FIELDS = [
        'id',
        'order_no',
        'customer_name',
        'order_date',
        'total',
        'item_discount',
        'tax_amount',
        'paid',
        'balance',
        'created_at',
    ];

    public $sessionId;

    public $session;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['refreshSalesList' => '$refresh'];

    public function mount($sessionId): void
    {
        $this->sessionId = $sessionId;
        $this->loadSession();
    }

    public function loadSession(): void
    {
        $this->session = SaleDaySession::with(['branch', 'opener', 'closer'])->find($this->sessionId);
    }

    public function updatingSearch(): void
    {
        $this->resetAllPages();
    }

    public function updatingPerPage(): void
    {
        $this->resetAllPages();
    }

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function render()
    {
        $salesQuery = $this->buildSalesQuery();
        $tailoringQuery = $this->buildTailoringQuery();

        $this->applySearch($salesQuery, $tailoringQuery);

        $paymentSummary = $this->getPaymentSummary();

        return view('livewire.sale-day-session.day-session-sales-list', [
            'sales' => $this->paginateSales($salesQuery),
            'totals' => $this->getSalesTotals(),
            'paymentSummary' => $paymentSummary,
            'paymentSummaryTotal' => $paymentSummary->sum('total_paid'),
            'combinedPayments' => $this->getCombinedPayments(),
            'tailoringOrders' => $this->paginateTailoringOrders($tailoringQuery),
            'tailoringTotals' => $this->getTailoringTotals(),
        ]);
    }

    private function resetAllPages(): void
    {
        $this->resetPage('salesPage');
        $this->resetPage('tailoringPage');
        $this->resetPage('paymentPage');
    }

    private function buildSalesQuery(): EloquentBuilder
    {
        return $this->sessionDataService()
            ->salesQueryForSession((int) $this->sessionId)
            ->with(['account', 'payments.paymentMethod']);
    }

    private function buildTailoringQuery(): EloquentBuilder
    {
        return $this->sessionDataService()
            ->tailoringOrdersQueryForSession((int) $this->sessionId)
            ->with(['account', 'payments.paymentMethod']);
    }

    private function applySearch(EloquentBuilder $salesQuery, EloquentBuilder $tailoringQuery): void
    {
        if (! $this->hasSearch()) {
            return;
        }

        $search = $this->searchTerm();

        $salesQuery->where(function ($query) use ($search): void {
            $query->where('invoice_no', 'like', $search)
                ->orWhere('customer_name', 'like', $search)
                ->orWhere('customer_mobile', 'like', $search)
                ->orWhere('id', 'like', $search);
        });

        $tailoringQuery->where(function ($query) use ($search): void {
            $query->where('order_no', 'like', $search)
                ->orWhere('customer_name', 'like', $search)
                ->orWhere('customer_mobile', 'like', $search)
                ->orWhere('id', 'like', $search);
        });
    }

    private function getSalesTotals(): array
    {
        return $this->sessionDataService()
            ->salesQueryForSession((int) $this->sessionId)
            ->selectRaw(self::TOTALS_SELECT)
            ->first()
            ->toArray();
    }

    private function getTailoringTotals(): array
    {
        return $this->sessionDataService()
            ->tailoringOrdersQueryForSession((int) $this->sessionId)
            ->selectRaw(self::TOTALS_SELECT)
            ->first()
            ->toArray();
    }

    private function getPaymentSummary(): Collection
    {
        return DB::query()
            ->fromSub($this->salePaymentSummaryUnion()->unionAll($this->tailoringPaymentSummaryUnion()), 'combined_payments')
            ->selectRaw('
                payment_method_id,
                payment_method_name,
                COUNT(DISTINCT transaction_ref) as count,
                COALESCE(SUM(amount), 0) as total_paid
            ')
            ->groupBy('payment_method_id', 'payment_method_name')
            ->orderBy('payment_method_name')
            ->get();
    }

    private function salePaymentSummaryUnion(): EloquentBuilder
    {
        return SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('accounts', 'sale_payments.payment_method_id', '=', 'accounts.id')
            ->whereDate('sale_payments.date', date('Y-m-d', strtotime($this->session->opened_at)))
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->selectRaw('
                sale_payments.payment_method_id as payment_method_id,
                accounts.name as payment_method_name,
                CONCAT("sale-", sale_payments.sale_id) as transaction_ref,
                sale_payments.amount as amount
            ');
    }

    private function tailoringPaymentSummaryUnion(): EloquentBuilder
    {
        return TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_payments.tailoring_order_id', '=', 'tailoring_orders.id')
            ->join('accounts', 'tailoring_payments.payment_method_id', '=', 'accounts.id')
            ->whereDate('tailoring_payments.date', date('Y-m-d', strtotime($this->session->opened_at)))
            ->whereNull('tailoring_orders.deleted_at')
            ->selectRaw('
                tailoring_payments.payment_method_id as payment_method_id,
                accounts.name as payment_method_name,
                CONCAT("tailoring-", tailoring_payments.tailoring_order_id) as transaction_ref,
                tailoring_payments.amount as amount
            ');
    }

    private function getCombinedPayments(): LengthAwarePaginator
    {
        $query = DB::query()->fromSub(
            $this->salePaymentTableUnion()->unionAll($this->tailoringPaymentTableUnion()),
            'combined_payments'
        );

        if ($this->hasSearch()) {
            $search = $this->searchTerm();

            $query->where(function ($builder) use ($search): void {
                $builder->where('reference_no', 'like', $search)
                    ->orWhere('customer_name', 'like', $search)
                    ->orWhere('customer_mobile', 'like', $search)
                    ->orWhere('payment_method_name', 'like', $search)
                    ->orWhere('source', 'like', $search);
            });
        }

        return $query
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage, ['*'], 'paymentPage');
    }

    private function salePaymentTableUnion(): EloquentBuilder
    {
        return SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->leftJoin('accounts as customers', 'sales.account_id', '=', 'customers.id')
            ->join('accounts as payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('sale_payments.date', date('Y-m-d', strtotime($this->session->opened_at)))
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->selectRaw('
                sale_payments.date as payment_date,
                "Sale" as source,
                sales.invoice_no as reference_no,
                COALESCE(customers.name, sales.customer_name) as customer_name,
                COALESCE(NULLIF(customers.mobile, ""), sales.customer_mobile) as customer_mobile,
                payment_methods.name as payment_method_name,
                sale_payments.amount as amount,
                sale_payments.created_at as created_at
            ');
    }

    private function tailoringPaymentTableUnion(): EloquentBuilder
    {
        return TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_payments.tailoring_order_id', '=', 'tailoring_orders.id')
            ->leftJoin('accounts as customers', 'tailoring_orders.account_id', '=', 'customers.id')
            ->join('accounts as payment_methods', 'tailoring_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('tailoring_payments.date', date('Y-m-d', strtotime($this->session->opened_at)))
            ->whereNull('tailoring_orders.deleted_at')
            ->selectRaw('
                tailoring_payments.date as payment_date,
                "Tailoring" as source,
                tailoring_orders.order_no as reference_no,
                COALESCE(customers.name, tailoring_orders.customer_name) as customer_name,
                COALESCE(NULLIF(customers.mobile, ""), tailoring_orders.customer_mobile) as customer_mobile,
                payment_methods.name as payment_method_name,
                tailoring_payments.amount as amount,
                tailoring_payments.created_at as created_at
            ');
    }

    private function paginateSales(EloquentBuilder $salesQuery): LengthAwarePaginator
    {
        return $salesQuery
            ->orderBy($this->validatedSortField(self::SALES_SORTABLE_FIELDS), $this->sortDirection)
            ->paginate($this->perPage, ['*'], 'salesPage');
    }

    private function paginateTailoringOrders(EloquentBuilder $tailoringQuery): LengthAwarePaginator
    {
        return $tailoringQuery
            ->orderBy($this->validatedSortField(self::TAILORING_SORTABLE_FIELDS), $this->sortDirection)
            ->paginate($this->perPage, ['*'], 'tailoringPage');
    }

    private function validatedSortField(array $allowedFields): string
    {
        return in_array($this->sortField, $allowedFields, true) ? $this->sortField : 'created_at';
    }

    private function hasSearch(): bool
    {
        return trim((string) $this->search) !== '';
    }

    private function searchTerm(): string
    {
        return '%'.trim((string) $this->search).'%';
    }

    private function sessionDataService(): SaleDaySessionDataService
    {
        return app(SaleDaySessionDataService::class);
    }
}
