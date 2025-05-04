<?php

namespace App\Livewire\Account\Customer;

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

    public $limit = 10;

    public $nationality;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'accounts.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Customer-Refresh-Component' => '$refresh',
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
            $exportFileName = 'customer_'.now()->timestamp.'.xlsx';
            $filters = [
                'account_type' => 'asset',
                'model' => 'customer',
            ];

            return Excel::download(new AccountExport($filters), $exportFileName);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = Account::where('model', 'customer')->latest()->limit(2000)->pluck('id')->toArray();
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
        $countries = Account::pluck('nationality', 'nationality')->toArray();
        $data = Account::orderBy($this->sortField, $this->sortDirection)
            ->when($this->nationality, function ($query, $value) {
                return $query->where('accounts.nationality', $value);
            })
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('accounts.mobile', 'like', "%{$value}%")
                        ->orWhere('accounts.email', 'like', "%{$value}%");
                });
            })
            ->where('account_type', 'asset')
            ->where('model', 'customer')
            ->latest()
            ->paginate($this->limit);

        return view('livewire.account.customer.table', [
            'countries' => $countries,
            'data' => $data,
        ]);
    }
}
