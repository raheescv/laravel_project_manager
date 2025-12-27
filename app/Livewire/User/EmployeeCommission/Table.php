<?php

namespace App\Livewire\User\EmployeeCommission;

use App\Actions\EmployeeCommission\DeleteAction;
use App\Models\EmployeeCommission;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $employee_id = '';

    public $product_id = '';

    public $sortField = 'employee_commissions.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'EmployeeCommission-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);
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
            $this->dispatch('RefreshEmployeeCommissionTable');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['selectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = EmployeeCommission::latest()->limit(2000)->pluck('id')->toArray();
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

    public function render()
    {
        $data = EmployeeCommission::with(['product.mainCategory', 'product.department'])
            ->join('users', 'users.id', '=', 'employee_commissions.employee_id')
            ->join('products', 'products.id', '=', 'employee_commissions.product_id')
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                $value = trim($value);
                return $query->where('users.name', 'like', "%{$value}%")
                    ->orWhere('products.name', 'like', "%{$value}%");
            })
            ->when($this->employee_id, function ($query) {
                return $query->where('employee_id', $this->employee_id);
            })
            ->when($this->product_id, function ($query) {
                return $query->where('product_id', $this->product_id);
            })
            ->paginate($this->limit);

        return view('livewire.user.employee-commission.table', ['data' => $data]);
    }
}
