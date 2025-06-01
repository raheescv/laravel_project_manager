<?php

namespace App\Livewire\Account\Income;

use App\Actions\Journal\DeleteAction;
use App\Exports\IncomeExport;
use App\Jobs\Export\ExportIncomeJob;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $filter = [];

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'journal_entries.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Income-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
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
            'from_date' => date('Y-m-01'),
            'to_date' => date('Y-m-d'),
            'account_id' => null,
            'search' => null,
            'branch_id' => session('branch_id'),
        ];
    }

    public function export()
    {
        $count = $this->dataFunction()->count();
        if ($count > 2000) {
            ExportIncomeJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'income_'.now()->timestamp.'.xlsx';

            return Excel::download(new IncomeExport($this->filter), $exportFileName);
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
        return JournalEntry::incomeList($this->filter);
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

        return view('livewire.account.income.table', [
            'data' => $data,
            'total' => $total,
        ]);
    }
}
