<?php

namespace App\Livewire\Report\Customer;

use App\Models\Sale;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerAging extends Component
{
    use WithPagination;

    public $customer_id;

    public $branch_id;

    public $from_date;

    public $to_date;

    public $perPage = 25;

    public $sortField = 'accounts.name';

    public $sortDirection = 'asc';

    // Totals
    public $totalInvoiceAmount = 0;

    public $totalAmountPaid = 0;

    public $totalOutstanding = 0;

    public $total0to30 = 0;

    public $total31to60 = 0;

    public $total61to90 = 0;

    public $total90Plus = 0;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['customerAgingFilterChanged' => 'filterChanged'];

    public function mount()
    {
        $this->from_date = date('Y-m-01', strtotime('-1 month'));
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null, $branch_id = null)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->customer_id = $customer_id;
        $this->branch_id = $branch_id;
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

    protected function calculateDueDate($invoiceDate, $creditPeriodDays, $saleDueDate = null)
    {
        // First priority: Use credit_period_days from customer account
        if ($creditPeriodDays) {
            return Carbon::parse($invoiceDate)->addDays($creditPeriodDays)->format('Y-m-d');
        }

        // Second priority: Use due_date from sale if available
        if ($saleDueDate) {
            return Carbon::parse($saleDueDate)->format('Y-m-d');
        }

        // Fallback: Use invoice date
        return $invoiceDate;
    }

    protected function calculateDaysOverdue($dueDate)
    {
        $due = Carbon::parse($dueDate);
        $today = Carbon::today();
        $daysOverdue = $today->diffInDays($due, false);

        return $daysOverdue > 0 ? 0 : abs($daysOverdue);
    }

    protected function getAgingBucket($daysOverdue, $outstanding)
    {
        if ($daysOverdue <= 0) {
            return ['0-30' => $outstanding, '31-60' => 0, '61-90' => 0, '90+' => 0];
        } elseif ($daysOverdue <= 30) {
            return ['0-30' => $outstanding, '31-60' => 0, '61-90' => 0, '90+' => 0];
        } elseif ($daysOverdue <= 60) {
            return ['0-30' => 0, '31-60' => $outstanding, '61-90' => 0, '90+' => 0];
        } elseif ($daysOverdue <= 90) {
            return ['0-30' => 0, '31-60' => 0, '61-90' => $outstanding, '90+' => 0];
        } else {
            return ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => $outstanding];
        }
    }

    protected function applySorting($collection)
    {
        $field = $this->sortField;
        $direction = $this->sortDirection;

        // Map field names to actual properties
        $fieldMap = [
            'accounts.name' => 'customer_name',
            'customer_name' => 'customer_name',
            'customer_mobile' => 'customer_mobile',
            'credit_period_days' => 'credit_period_days',
            'sales.invoice_no' => 'invoice_no',
            'invoice_no' => 'invoice_no',
            'sales.date' => 'invoice_date',
            'invoice_date' => 'invoice_date',
            'due_date' => 'due_date',
            'days_overdue' => 'days_overdue',
            'sales.grand_total' => 'invoice_amount',
            'invoice_amount' => 'invoice_amount',
            'sales.paid' => 'amount_paid',
            'amount_paid' => 'amount_paid',
            'sales.balance' => 'outstanding_balance',
            'outstanding_balance' => 'outstanding_balance',
        ];

        $property = $fieldMap[$field] ?? $field;

        $sorted = $collection->sortBy(function ($item) use ($property) {
            $value = $item->{$property} ?? null;

            // Convert dates to timestamps for proper sorting
            if (in_array($property, ['invoice_date', 'due_date']) && $value) {
                return strtotime($value);
            }

            // Handle numeric values
            if (in_array($property, ['invoice_amount', 'amount_paid', 'outstanding_balance', 'days_overdue', 'credit_period_days', 'aging_0_30', 'aging_31_60', 'aging_61_90', 'aging_90_plus'])) {
                return (float) ($value ?? 0);
            }

            return $value ?? '';
        }, SORT_REGULAR, $direction === 'desc');

        return $sorted->values();
    }

    public function render()
    {
        $today = Carbon::today();

        $query = Sale::query()
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->completed()
            ->where('sales.balance', '>', 0) // Only show sales with outstanding balance
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('sales.account_id', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->select(
                'accounts.id as account_id',
                'accounts.name as customer_name',
                'accounts.mobile as customer_mobile',
                'accounts.credit_period_days',
                'sales.id',
                'sales.invoice_no',
                'sales.date as invoice_date',
                'sales.due_date as sale_due_date',
                'sales.grand_total as invoice_amount',
                'sales.paid as amount_paid',
                'sales.balance as outstanding_balance'
            );

        // Get all data for calculations
        $allSales = $query->get();

        // Process each sale to add calculated fields
        $processedSales = $allSales->map(function ($sale) {
            // Calculate due date
            $dueDate = $this->calculateDueDate($sale->invoice_date, $sale->credit_period_days, $sale->sale_due_date ?? null);

            // Calculate days overdue
            $daysOverdue = $this->calculateDaysOverdue($dueDate);

            // Get aging buckets
            $aging = $this->getAgingBucket($daysOverdue, $sale->outstanding_balance);

            return (object) array_merge($sale->toArray(), [
                'due_date' => $dueDate,
                'days_overdue' => $daysOverdue,
                'aging_0_30' => $aging['0-30'],
                'aging_31_60' => $aging['31-60'],
                'aging_61_90' => $aging['61-90'],
                'aging_90_plus' => $aging['90+'],
            ]);
        });

        // Apply sorting
        $processedSales = $this->applySorting($processedSales);

        // Calculate totals
        $this->totalInvoiceAmount = $processedSales->sum('invoice_amount');
        $this->totalAmountPaid = $processedSales->sum('amount_paid');
        $this->totalOutstanding = $processedSales->sum('outstanding_balance');
        $this->total0to30 = $processedSales->sum('aging_0_30');
        $this->total31to60 = $processedSales->sum('aging_31_60');
        $this->total61to90 = $processedSales->sum('aging_61_90');
        $this->total90Plus = $processedSales->sum('aging_90_plus');

        // Paginate the processed sales
        $currentPage = $this->getPage();
        $perPage = $this->perPage;
        $items = $processedSales->forPage($currentPage, $perPage);
        $total = $processedSales->count();

        // Create paginator manually
        $sales = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return view('livewire.report.customer.customer-aging', [
            'sales' => $sales,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
