<?php

namespace App\Livewire\Report;

use App\Exports\TaxReportExport;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class TaxReport extends Component
{
    use WithPagination;

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $transaction_type = 'all'; // all, purchase, sale

    public $limit = 25;

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

    public function getReportData()
    {
        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;
        $taxAccountId = $this->getTaxAccountId();

        if (!$taxAccountId) {
            return collect([]);
        }

        $query = JournalEntry::with(['account', 'journal'])
            ->where('account_id', $taxAccountId)
            ->when($from, fn($q) => $q->where('date', '>=', $from))
            ->when($to, fn($q) => $q->where('date', '<=', $to))
            // ->when($this->branch_id, fn($q) => $q->where('branch_id', $this->branch_id))
            ->when($this->transaction_type === 'purchase', function ($q) {
                $q->whereIn('model', ['Purchase', 'PurchaseReturn']);
            })
            ->when($this->transaction_type === 'sale', function ($q) {
                $q->whereIn('model', ['Sale', 'SaleReturn']);
            })
            ->when($this->transaction_type === 'all', function ($q) {
                $q->whereIn('model', ['Purchase', 'PurchaseReturn', 'Sale', 'SaleReturn']);
            });

        // Apply sorting
        if ($this->sortField === 'date') {
            $query->orderBy('date', $this->sortDirection)->orderBy('id', $this->sortDirection);
        } elseif ($this->sortField === 'debit') {
            $query->orderBy('debit', $this->sortDirection)->orderBy('date', 'desc');
        } elseif ($this->sortField === 'credit') {
            $query->orderBy('credit', $this->sortDirection)->orderBy('date', 'desc');
        } elseif ($this->sortField === 'model') {
            $query->orderBy('model', $this->sortDirection)->orderBy('date', 'desc');
        } else {
            $query->orderBy('date', $this->sortDirection)->orderBy('id', $this->sortDirection);
        }
        return $query;
    }

    public function export()
    {
        $query = $this->getReportData();
        $entries = $query->get();

        $filters = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'transaction_type' => $this->transaction_type,
        ];

        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;
        $exportFileName = 'TaxReport_' . ($from ? systemDate($from) : '') . '_' . ($to ? systemDate($to) : '') . '_' . now()->timestamp . '.xlsx';

        return Excel::download(new TaxReportExport($entries, $filters), $exportFileName);
    }

    public function render()
    {
        $query = $this->getReportData();
        $entries = $query->paginate($this->limit);
        // Calculate totals
        $totalQuery = $this->getReportData();
        $totals = $totalQuery
            ->selectRaw(
                '
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit,
                    COUNT(*) as total_count
                ',
            )
            ->first();

        return view('livewire.report.tax-report', [
            'entries' => $entries,
            'totals' => $totals,
        ]);
    }
}
