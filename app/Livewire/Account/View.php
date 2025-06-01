<?php

namespace App\Livewire\Account;

use App\Actions\Journal\DeleteAction;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Models\Views\Ledger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

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

    protected $paginationTheme = 'bootstrap';

    public function mount($account_id)
    {
        $this->accountId = $account_id;
        if ($this->accountId) {
            $this->account = Account::findOrFail($this->accountId);
        }
        $this->filter = [
            'from_date' => date('Y-m-01'),
            'to_date' => date('Y-m-d'),
            'search' => '',
            'account_id' => $this->accountId,
            'branch_id' => session('branch_id'),
        ];
        $this->lineChartData();
        $this->groupedChartData();
    }

    public function lineChartData()
    {
        $start = date('Y-m-d', strtotime('-12 months'));
        $end = date('Y-m-d');
        $this->lineChartData = Ledger::monthly_summary($start, $end, $this->accountId);
    }

    public function groupedChartData()
    {
        $this->groupedChartData = $this->dataFunction()
            ->select('account_id')
            ->selectRaw('account_id, SUM(debit) as debit, SUM(credit) as credit')
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

    private function dataFunction()
    {
        return JournalEntry::with('account')->where('counter_account_id', $this->accountId)
            ->when($this->filter['search'], function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('journal_entries.reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_entries.journal_remarks', 'like', "%{$value}%")
                        ->orWhere('journal_entries.remarks', 'like', "%{$value}%");
                });
            })
            ->when($this->filter['from_date'] ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filter['to_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            });
    }

    public function render()
    {
        $data = $this->dataFunction();
        $totalRow = clone $data;

        $data = $data->orderBy($this->sortField, $this->sortDirection)->paginate($this->limit);

        $totalRow = $totalRow->selectRaw('SUM(debit) as debit, SUM(credit) as credit')->first();
        $total['debit'] = $totalRow->debit;
        $total['credit'] = $totalRow->credit;

        return view('livewire.account.view', ['data' => $data, 'total' => $total]);
    }
}
