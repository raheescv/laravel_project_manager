<?php

namespace App\Livewire\Account;

use App\Exports\AccountViewExport;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Models\Views\Ledger;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class View extends Component
{
    use WithPagination;

    public $groupedChartData;

    public $lineChartData;

    public $accountId;

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $filter;

    public $account;

    public $sortField = 'journal_entries.id';

    public $sortDirection = 'desc';

    public $excludeOpeningFromTotal = false;

    protected $paginationTheme = 'bootstrap';

    public function mount($account_id)
    {
        $this->accountId = $account_id;
        if ($this->accountId) {
            $this->account = Account::findOrFail($this->accountId);
        }

        $this->filter = [
            'from_date' => request()->get('from_date', date('Y-m-01')),
            'to_date' => request()->get('to_date', date('Y-m-d')),
            'search' => '',
            'account_id' => $this->accountId,
            'branch_id' => session('branch_id'),
        ];
        if (in_array($this->account->account_type, ['income', 'expense'])) {
            $this->excludeOpeningFromTotal = true;
        }
        $this->lineChartData();
        $this->groupedChartData();
    }

    public function lineChartData()
    {
        $start = date('Y-m-d', strtotime('-12 months'));
        $end = date('Y-m-d');
        $this->lineChartData = Ledger::monthly_summary($start, $end, $this->accountId);
    }

    public function groupedChartData(): void
    {
        $this->groupedChartData = $this->dataFunction()
            ->select('account_id')
            ->selectRaw('account_id, ROUND(SUM(debit),2) as debit, ROUND(SUM(credit),2) as credit')
            ->groupBy('account_id')
            ->orderBy('account_id')
            ->get();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->dataFunction()->limit(2000)->pluck('journal_id')->toArray();
        } else {
            $this->selected = [];
        }
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
        $this->lineChartData();
        $this->groupedChartData();
        $this->dispatch('propertyUpdated');
    }

    private function dataFunction()
    {
        return $this->baseQuery()
            ->when($this->filter['search'] ?? '', fn ($query, $value) => $this->applySearchFilter($query, $value))
            ->when($this->filter['from_date'] ?? '', fn ($query, $value) => $this->applyFromDateFilter($query, $value))
            ->when($this->filter['to_date'] ?? '', fn ($query, $value) => $this->applyToDateFilter($query, $value));
    }

    private function baseQuery()
    {
        return JournalEntry::with('account')
            ->where('counter_account_id', $this->accountId);
    }

    private function applySearchFilter($query, string $value)
    {
        $value = trim($value);

        return $query->where(function ($q) use ($value) {
            return $q->where('description', 'like', "%{$value}%")
                ->orWhere('journal_entries.reference_number', 'like', "%{$value}%")
                ->orWhere('journal_entries.journal_remarks', 'like', "%{$value}%")
                ->orWhere('journal_entries.remarks', 'like', "%{$value}%");
        });
    }

    private function applyFromDateFilter($query, string $value)
    {
        return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
    }

    private function applyToDateFilter($query, string $value)
    {
        return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
    }

    public function getOpeningBalance(): array
    {
        if ($this->excludeOpeningFromTotal) {
            return ['debit' => 0, 'credit' => 0];
        }

        $openingBalance = $this->getOpeningBalanceQuery()->first();

        return [
            'debit' => $openingBalance->debit ?? ($this->account->opening_debit ?? 0),
            'credit' => $openingBalance->credit ?? ($this->account->opening_credit ?? 0),
        ];
    }

    private function getOpeningBalanceQuery()
    {
        return $this->baseQuery()
            ->when($this->filter['from_date'] ?? '', fn ($query, $value) => $query->where('date', '<', date('Y-m-d', strtotime($value)))
            )
            ->selectRaw('ROUND(SUM(debit),2) as debit, ROUND(SUM(credit),2) as credit');
    }

    public function export()
    {
        try {
            $fileName = $this->generateExportFileName();

            return Excel::download(
                new AccountViewExport($this->account, $this->filter, $this->excludeOpeningFromTotal),
                $fileName
            );
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
        }
    }

    private function generateExportFileName(): string
    {
        $accountName = str_replace(' ', '_', $this->account->name);
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "Account_Ledger_{$accountName}_{$timestamp}.xlsx";
    }

    public function render()
    {
        $data = $this->dataFunction();
        $totalRow = clone $data;

        $data = $data->orderBy('date', 'ASC')->paginate($this->limit);

        $total = $this->calculateTotals($totalRow);
        $openingBalance = $this->getOpeningBalance();

        return view('livewire.account.view', [
            'data' => $data,
            'total' => $total,
            'openingBalance' => $openingBalance,
        ]);
    }

    private function calculateTotals($query): array
    {
        $totalRow = $query->selectRaw('ROUND(SUM(debit),2) as debit, ROUND(SUM(credit),2) as credit')->first();

        return [
            'debit' => $totalRow->debit ?? 0,
            'credit' => $totalRow->credit ?? 0,
        ];
    }
}
