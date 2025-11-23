<?php

namespace App\Livewire\Account;

use App\Actions\Account\DeleteAction;
use App\Exports\AccountExport;
use App\Jobs\Export\ExportAccountJob;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $account_type = '';

    public $account_category_id = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'accounts.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Account-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);
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

    public function export()
    {
        $count = Account::count();
        if ($count > 2000) {
            ExportAccountJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'account_'.now()->timestamp.'.xlsx';

            return Excel::download(new AccountExport(), $exportFileName);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = Account::latest()->limit(2000)->pluck('id')->toArray();
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

    public function render()
    {
        $data = Account::with('accountCategory:id,name')->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('accounts.mobile', 'like', "%{$value}%")
                        ->orWhere('accounts.email', 'like', "%{$value}%")
                        ->orWhere('accounts.model', 'like', "%{$value}%");
                });
            })
            ->when($this->account_type ?? '', function ($query, $value) {
                return $query->where('account_type', $value);
            })
            ->when($this->account_category_id ?? '', function ($query, $value) {
                return $query->where('account_category_id', $value);
            })
            ->when($this->model ?? '', function ($query, $value) {
                return $query->where('model', $value);
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.account.table', [
            'data' => $data,
        ]);
    }
}
