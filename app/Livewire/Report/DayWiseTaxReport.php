<?php

namespace App\Livewire\Report;

use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DayWiseTaxReport extends Component
{
    use WithPagination;

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $transaction_type = 'all'; // all, purchase, sale

    public $limit = 10;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    private function getTaxAccountId()
    {
        $accounts = Cache::get('accounts_slug_id_map', []);

        return $accounts['tax_amount'] ?? null;
    }

    private function getReportData()
    {
        // Standardize dates
        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;
        $taxAccountId = $this->getTaxAccountId();

        if (! $taxAccountId) {
            return [[], []];
        }

        $summary = [];

        // Initialize summary structure for sale-only mode
        if ($this->transaction_type === 'sale') {
            // We'll populate this when processing sale data
        }

        // Get Purchase Tax Credit (debit entries from Purchase)
        if ($this->transaction_type === 'all' || $this->transaction_type === 'purchase') {
            $purchaseTax = JournalEntry::query()
                ->where('account_id', $taxAccountId)
                ->where('model', 'Purchase')
                ->where('debit', '>', 0)
                ->when($from, fn ($q) => $q->where('date', '>=', $from))
                ->when($to, fn ($q) => $q->where('date', '<=', $to))
                ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
                ->select(
                    'date',
                    DB::raw('SUM(debit) as tax_credit')
                )
                ->groupBy('date')
                ->toBase()
                ->get()
                ->keyBy('date');

            // Get Purchase Return Tax Credit Reduction (credit entries from PurchaseReturn)
            $purchaseReturnTax = JournalEntry::query()
                ->where('account_id', $taxAccountId)
                ->where('model', 'PurchaseReturn')
                ->where('credit', '>', 0)
                ->when($from, fn ($q) => $q->where('date', '>=', $from))
                ->when($to, fn ($q) => $q->where('date', '<=', $to))
                ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
                ->select(
                    'date',
                    DB::raw('SUM(credit) as tax_credit_reduction')
                )
                ->groupBy('date')
                ->toBase()
                ->get()
                ->keyBy('date');

            // Merge purchase data
            foreach ($purchaseTax as $item) {
                $key = $item->date;
                $summary[$key] = [
                    'date' => $key,
                    'purchase_tax_credit' => (float) $item->tax_credit,
                    'purchase_return_tax_credit' => 0,
                    'sale_tax_amount' => 0,
                    'sale_return_tax_amount' => 0,
                ];
            }

            foreach ($purchaseReturnTax as $item) {
                $key = $item->date;
                if (! isset($summary[$key])) {
                    $summary[$key] = [
                        'date' => $key,
                        'purchase_tax_credit' => 0,
                        'purchase_return_tax_credit' => 0,
                        'sale_tax_amount' => 0,
                        'sale_return_tax_amount' => 0,
                    ];
                }
                $summary[$key]['purchase_return_tax_credit'] = (float) $item->tax_credit_reduction;
            }
        }

        // Get Sale Tax Liability (credit entries from Sale)
        if ($this->transaction_type === 'all' || $this->transaction_type === 'sale') {
            $saleTax = JournalEntry::query()
                ->where('account_id', $taxAccountId)
                ->where('model', 'Sale')
                ->where('credit', '>', 0)
                ->when($from, fn ($q) => $q->where('date', '>=', $from))
                ->when($to, fn ($q) => $q->where('date', '<=', $to))
                ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
                ->select(
                    'date',
                    DB::raw('SUM(credit) as tax_liability')
                )
                ->groupBy('date')
                ->toBase()
                ->get()
                ->keyBy('date');

            // Get Sale Return Tax Liability Reduction (debit entries from SaleReturn)
            $saleReturnTax = JournalEntry::query()
                ->where('account_id', $taxAccountId)
                ->where('model', 'SaleReturn')
                ->where('debit', '>', 0)
                ->when($from, fn ($q) => $q->where('date', '>=', $from))
                ->when($to, fn ($q) => $q->where('date', '<=', $to))
                ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
                ->select(
                    'date',
                    DB::raw('SUM(debit) as tax_liability_reduction')
                )
                ->groupBy('date')
                ->toBase()
                ->get()
                ->keyBy('date');

            // Merge sale data
            foreach ($saleTax as $item) {
                $key = $item->date;
                if (! isset($summary[$key])) {
                    $summary[$key] = [
                        'date' => $key,
                        'purchase_tax_credit' => 0,
                        'purchase_return_tax_credit' => 0,
                        'sale_tax_amount' => 0,
                        'sale_return_tax_amount' => 0,
                    ];
                }
                $summary[$key]['sale_tax_amount'] = (float) $item->tax_liability;
            }

            foreach ($saleReturnTax as $item) {
                $key = $item->date;
                if (! isset($summary[$key])) {
                    $summary[$key] = [
                        'date' => $key,
                        'purchase_tax_credit' => 0,
                        'purchase_return_tax_credit' => 0,
                        'sale_tax_amount' => 0,
                        'sale_return_tax_amount' => 0,
                    ];
                }
                $summary[$key]['sale_return_tax_amount'] = (float) $item->tax_liability_reduction;
            }
        }

        // Calculate net values for each date
        foreach ($summary as $key => &$item) {
            $item['net_tax_credit'] = $item['purchase_tax_credit'] - $item['purchase_return_tax_credit'];
            $item['net_tax_liability'] = $item['sale_tax_amount'] - $item['sale_return_tax_amount'];
            $item['net_tax_payable'] = $item['net_tax_liability'] - $item['net_tax_credit'];
        }

        // Compute totals
        $total = [
            'purchase_tax_credit' => array_sum(array_column($summary, 'purchase_tax_credit')),
            'purchase_return_tax_credit' => array_sum(array_column($summary, 'purchase_return_tax_credit')),
            'sale_tax_amount' => array_sum(array_column($summary, 'sale_tax_amount')),
            'sale_return_tax_amount' => array_sum(array_column($summary, 'sale_return_tax_amount')),
            'net_tax_credit' => array_sum(array_column($summary, 'net_tax_credit')),
            'net_tax_liability' => array_sum(array_column($summary, 'net_tax_liability')),
            'net_tax_payable' => array_sum(array_column($summary, 'net_tax_payable')),
        ];

        return [array_values($summary), $total];
    }

    public function render()
    {
        [$summary, $total] = $this->getReportData();

        // Sort the summary
        usort($summary, function ($a, $b) {
            $field = $this->sortField;
            $direction = $this->sortDirection === 'asc' ? 1 : -1;

            if ($field === 'date') {
                return $direction * strcmp($a['date'], $b['date']);
            }

            // Map sort field to array key
            $fieldMap = [
                'purchase_tax_credit' => 'purchase_tax_credit',
                'purchase_return_tax_credit' => 'purchase_return_tax_credit',
                'sale_tax_amount' => 'sale_tax_amount',
                'sale_return_tax_amount' => 'sale_return_tax_amount',
                'net_tax_credit' => 'net_tax_credit',
                'net_tax_liability' => 'net_tax_liability',
                'net_tax_payable' => 'net_tax_payable',
            ];

            $key = $fieldMap[$field] ?? 'date';
            $valueA = $a[$key] ?? 0;
            $valueB = $b[$key] ?? 0;

            return $direction * ($valueA <=> $valueB);
        });

        return view('livewire.report.day-wise-tax-report', [
            'data' => $summary,
            'total' => $total,
        ]);
    }
}
