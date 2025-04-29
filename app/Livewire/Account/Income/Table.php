<?php

namespace App\Livewire\Account\Income;

use App\Actions\Journal\DeleteAction;
use App\Exports\IncomeExport;
use App\Jobs\Export\ExportIncomeJob;
use App\Models\Models\Views\Ledger;
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

    public $sortField = 'ledgers.id';

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
        return Ledger::incomeList($this->filter)
            ->select(
                'id',
                'account_id',
                'journal_id',
                'date',
                'account_name',
                'description',
                'reference_number',
                'model',
                'person_name',
                'model_id',
                'remarks',
                'debit',
                'credit',
                'balance'
            );
    }

    public function render()
    {
        $data = $this->dataFunction();
        $data = $data->orderBy($this->sortField, $this->sortDirection);

        $totalRow = clone $data;

        $data = $data->paginate($this->limit);

        $total['debit'] = $totalRow->sum('debit');
        $total['credit'] = $totalRow->sum('credit');

        return view('livewire.account.income.table', [
            'data' => $data,
            'total' => $total,
        ]);
    }
}
