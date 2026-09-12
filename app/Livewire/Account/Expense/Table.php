<?php

namespace App\Livewire\Account\Expense;

use App\Actions\Journal\DeleteAction;
use App\Exports\ExpenseExport;
use App\Jobs\Export\ExportExpenseJob;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    /** Number of account heads shown as individual slices on the pie chart. */
    private const CHART_LIMIT = 10;

    public $filter = [
        'from_date' => null,
        'to_date' => null,
        'account_id' => null,
        'search' => null,
        'branch_id' => null,
    ];

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'journal_entries.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Expense-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        abort_unless(auth()->user()?->can('expense.delete'), 403);
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id, Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshAccountTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function mount()
    {
        $this->filter = [
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
            'account_id' => null,
            'search' => null,
            'branch_id' => session('branch_id'),
        ];
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('expense.export'), 403);
        $count = $this->dataFunction()->count();
        if ($count > 2000) {
            ExportExpenseJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'expense_'.now()->timestamp.'.xlsx';

            return Excel::download(new ExpenseExport($this->filter), $exportFileName);
        }
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
    }

    private function dataFunction()
    {
        return JournalEntry::expenseList($this->filter);
    }

    private function topAccountsChart()
    {
        $rows = $this->dataFunction()
            ->groupBy('journal_entries.account_id')
            ->selectRaw('journal_entries.account_id as account_id, SUM(journal_entries.debit) as total')
            ->orderByDesc('total')
            ->get();

        $names = Account::whereIn('id', $rows->pluck('account_id'))->pluck('name', 'id');
        $grandTotal = (float) $rows->sum('total');

        $slices = [];
        foreach ($rows->take(self::CHART_LIMIT) as $row) {
            $value = round((float) $row->total, 2);
            $slices[] = [
                'label' => $names[$row->account_id] ?? 'Unknown Account',
                'value' => $value,
                'percent' => $grandTotal > 0 ? round($value / $grandTotal * 100, 1) : 0,
                'other' => false,
            ];
        }

        $remaining = $rows->slice(self::CHART_LIMIT);
        $otherTotal = round((float) $remaining->sum('total'), 2);
        if ($otherTotal > 0) {
            $slices[] = [
                'label' => 'Other ('.$remaining->count().' '.Str::plural('account', $remaining->count()).')',
                'value' => $otherTotal,
                'percent' => $grandTotal > 0 ? round($otherTotal / $grandTotal * 100, 1) : 0,
                'other' => true,
            ];
        }

        return [
            'slices' => $slices,
            'total' => round($grandTotal, 2),
            'accounts' => $rows->count(),
            'limit' => self::CHART_LIMIT,
        ];
    }

    private function calculateTotals($query)
    {
        return $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();
    }

    public function render()
    {
        $query = $this->dataFunction();
        $totals = $this->calculateTotals($query);
        $data = $query
            ->join('accounts', 'accounts.id', '=', 'journal_entries.account_id')
            ->select([
                   'journal_entries.id',
                    'account_id',
                    'journal_id',
                    'date',
                    'accounts.name as account_name',
                    'journal_entries.description',
                    'reference_number',
                    'journal_entries.model',
                    'person_name',
                    'journal_entries.model_id',
                    'journal_entries.remarks',
                    'debit',
                    'credit',
             ])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $total = [
            'debit' => round($totals->total_debit, 2),
            'credit' => round($totals->total_credit, 2),
        ];

        return view('livewire.account.expense.table', [
            'data' => $data,
            'total' => $total,
            'chart' => $this->topAccountsChart(),
        ]);
    }
}
