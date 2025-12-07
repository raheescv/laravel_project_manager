<?php

namespace App\Livewire\Account\GeneralVoucher;

use App\Actions\Journal\DeleteAction;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Exception;

class Table extends Component
{
    use WithPagination;

    public $filter = [
        'from_date' => null,
        'to_date' => null,
        'search' => null,
        'branch_id' => null,
        'account_id' => null,
    ];

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'GeneralVoucher-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $journalId) {
                $response = (new DeleteAction())->execute($journalId, Auth::id());
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshGeneralVoucherTable');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function mount()
    {
        $this->filter = [
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
            'search' => null,
            'branch_id' => session('branch_id'),
        ];
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->dataFunction()->limit(2000)->pluck('journal_id')->unique()->toArray();
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
        return JournalEntry::where('source', 'General Voucher')
            ->when($this->filter['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_remarks', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%")
                        ->orWhere('person_name', 'like', "%{$value}%");
                });
            })
            ->when($this->filter['from_date'] ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filter['to_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filter['branch_id'] ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->filter['account_id'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    return $q->where('account_id', $value);
                });
            });
    }

    public function render()
    {
        $sortField = $this->sortField;
        $query = $this->dataFunction();

        // Calculate totals for all filtered records (before pagination)
        $totalRow = clone $query;
        $totalDebit = $totalRow->sum('debit');
        $totalCredit = $totalRow->sum('credit');

        $data = $query
            ->with(['journal.entries.account', 'journal.createdBy', 'account', 'counterAccount'])
            ->orderBy($sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.account.general-voucher.table', [
            'data' => $data,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }
}
