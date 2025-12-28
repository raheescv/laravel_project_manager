<?php

namespace App\Livewire\Report\Vendor;

use App\Exports\VendorAgingExport;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class VendorAging extends Component
{
    use WithPagination;

    public $vendor_id;

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

    protected $listeners = ['vendorAgingFilterChanged' => 'filterChanged'];

    public function mount()
    {
        $this->from_date = date('Y-m-01', strtotime('-1 month'));
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $vendor_id = null, $branch_id = null)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->vendor_id = $vendor_id;
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

    protected function calculateDueDate($invoiceDate, $creditPeriodDays, $deliveryDate = null)
    {
        // First priority: Use credit_period_days from vendor account
        if ($creditPeriodDays) {
            return Carbon::parse($invoiceDate)->addDays($creditPeriodDays)->format('Y-m-d');
        }

        // Second priority: Use delivery_date from purchase if available
        if ($deliveryDate) {
            return Carbon::parse($deliveryDate)->format('Y-m-d');
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

    protected function getBaseQuery()
    {
        return Purchase::query()
            ->join('accounts', 'purchases.account_id', '=', 'accounts.id')
            ->where('purchases.status', 'completed')
            ->where('purchases.balance', '>', 0) // Only show purchases with outstanding balance
            ->when($this->branch_id, fn ($q, $value) => $q->where('purchases.branch_id', $value))
            ->when($this->vendor_id, fn ($q, $value) => $q->where('purchases.account_id', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('purchases.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('purchases.date', '<=', date('Y-m-d', strtotime($value))))
            ->select('accounts.id as account_id', 'accounts.name as vendor_name', 'accounts.mobile as vendor_mobile', 'accounts.credit_period_days', 'purchases.id', 'purchases.invoice_no', 'purchases.date as invoice_date', 'purchases.delivery_date', 'purchases.grand_total as invoice_amount', 'purchases.paid as amount_paid', 'purchases.balance as outstanding_balance');
    }

    protected function getProcessedPurchases($applySorting = true)
    {
        // Get all data for calculations
        $allPurchases = $this->getBaseQuery()->get();

        // Process each purchase to add calculated fields
        $processedPurchases = $allPurchases->map(function ($purchase) {
            // Calculate due date
            $dueDate = $this->calculateDueDate($purchase->invoice_date, $purchase->credit_period_days, $purchase->delivery_date ?? null);

            // Calculate days overdue
            $daysOverdue = $this->calculateDaysOverdue($dueDate);

            // Get aging buckets
            $aging = $this->getAgingBucket($daysOverdue, $purchase->outstanding_balance);

            return (object) array_merge($purchase->toArray(), [
                'due_date' => $dueDate,
                'days_overdue' => $daysOverdue,
                'aging_0_30' => $aging['0-30'],
                'aging_31_60' => $aging['31-60'],
                'aging_61_90' => $aging['61-90'],
                'aging_90_plus' => $aging['90+'],
            ]);
        });

        // Apply sorting if requested
        if ($applySorting) {
            $processedPurchases = $this->applySorting($processedPurchases);
        }

        return $processedPurchases;
    }

    protected function applySorting($collection)
    {
        $field = $this->sortField;
        $direction = $this->sortDirection;

        // Map field names to actual properties
        $fieldMap = [
            'accounts.name' => 'vendor_name',
            'vendor_name' => 'vendor_name',
            'vendor_mobile' => 'vendor_mobile',
            'credit_period_days' => 'credit_period_days',
            'purchases.invoice_no' => 'invoice_no',
            'invoice_no' => 'invoice_no',
            'purchases.date' => 'invoice_date',
            'invoice_date' => 'invoice_date',
            'due_date' => 'due_date',
            'days_overdue' => 'days_overdue',
            'purchases.grand_total' => 'invoice_amount',
            'invoice_amount' => 'invoice_amount',
            'purchases.paid' => 'amount_paid',
            'amount_paid' => 'amount_paid',
            'purchases.balance' => 'outstanding_balance',
            'outstanding_balance' => 'outstanding_balance',
        ];

        $property = $fieldMap[$field] ?? $field;

        $sorted = $collection->sortBy(
            function ($item) use ($property) {
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
            },
            SORT_REGULAR,
            $direction === 'desc',
        );

        return $sorted->values();
    }

    public function export()
    {
        $processedPurchases = $this->getProcessedPurchases();

        $totals = [
            'totalInvoiceAmount' => $processedPurchases->sum('invoice_amount'),
            'totalAmountPaid' => $processedPurchases->sum('amount_paid'),
            'totalOutstanding' => $processedPurchases->sum('outstanding_balance'),
            'total0to30' => $processedPurchases->sum('aging_0_30'),
            'total31to60' => $processedPurchases->sum('aging_31_60'),
            'total61to90' => $processedPurchases->sum('aging_61_90'),
            'total90Plus' => $processedPurchases->sum('aging_90_plus'),
        ];

        $filters = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'vendor_id' => $this->vendor_id,
            'branch_id' => $this->branch_id,
        ];

        $exportFileName = 'Vendor_Aging_Report_'.now()->timestamp.'.xlsx';

        return Excel::download(new VendorAgingExport($processedPurchases, $totals, $filters), $exportFileName);
    }

    public function render()
    {
        // Get processed purchases with sorting
        $processedPurchases = $this->getProcessedPurchases();

        // Calculate totals
        $this->totalInvoiceAmount = $processedPurchases->sum('invoice_amount');
        $this->totalAmountPaid = $processedPurchases->sum('amount_paid');
        $this->totalOutstanding = $processedPurchases->sum('outstanding_balance');
        $this->total0to30 = $processedPurchases->sum('aging_0_30');
        $this->total31to60 = $processedPurchases->sum('aging_31_60');
        $this->total61to90 = $processedPurchases->sum('aging_61_90');
        $this->total90Plus = $processedPurchases->sum('aging_90_plus');

        // Paginate the processed purchases
        $currentPage = $this->getPage();
        $perPage = $this->perPage;
        $items = $processedPurchases->forPage($currentPage, $perPage);
        $total = $processedPurchases->count();

        // Create paginator manually
        $purchases = new LengthAwarePaginator($items, $total, $perPage, $currentPage, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);

        return view('livewire.report.vendor.vendor-aging', [
            'purchases' => $purchases,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
