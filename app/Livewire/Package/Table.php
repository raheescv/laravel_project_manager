<?php

namespace App\Livewire\Package;

use App\Actions\Package\DeleteAction;
use App\Models\Package;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $package_category_id = '';

    public $account_id = '';

    public $from_date = '';

    public $to_date = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Package-Refresh-Component' => '$refresh',
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
            $this->dispatch('RefreshPackageTable');
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = Package::query();
            if ($this->search ?? '') {
                $query->where(function ($q) {
                    $value = trim($this->search);
                    $q->where('id', 'like', "%{$value}%")
                        ->orWhereHas('packageCategory', function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%");
                        })
                        ->orWhereHas('account', function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%")
                                ->orWhere('mobile', 'like', "%{$value}%");
                        });
                });
            }
            if ($this->status ?? '') {
                $query->where('status', $this->status);
            }
            if ($this->package_category_id ?? '') {
                $query->where('package_category_id', $this->package_category_id);
            }
            if ($this->account_id ?? '') {
                $query->where('account_id', $this->account_id);
            }
            if ($this->from_date ?? '') {
                $query->where('end_date', '>=', $this->from_date);
            }
            if ($this->to_date ?? '') {
                $query->where('start_date', '<=', $this->to_date);
            }
            $this->selected = $query->limit(2000)->pluck('id')->toArray();
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
        $needsJoin = str_contains($this->sortField, 'package_categories.') || str_contains($this->sortField, 'accounts.');
        
        $query = Package::query();

        // Join tables for sorting on related fields
        if ($needsJoin) {
            $query->leftJoin('package_categories', 'packages.package_category_id', '=', 'package_categories.id')
                ->leftJoin('accounts', 'packages.account_id', '=', 'accounts.id')
                ->select('packages.*');
        }

        $query->when($this->search ?? '', function ($query, $value) use ($needsJoin) {
            return $query->where(function ($q) use ($value, $needsJoin) {
                $value = trim($value);
                $idField = $needsJoin ? 'packages.id' : 'id';
                $q->where($idField, 'like', "%{$value}%")
                    ->orWhereHas('packageCategory', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%");
                    })
                    ->orWhereHas('account', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhere('mobile', 'like', "%{$value}%");
                    });
            });
        })
        ->when($this->status ?? '', function ($query, $value) use ($needsJoin) {
            $statusField = $needsJoin ? 'packages.status' : 'status';
            return $query->where($statusField, $value);
        })
        ->when($this->package_category_id ?? '', function ($query, $value) use ($needsJoin) {
            $field = $needsJoin ? 'packages.package_category_id' : 'package_category_id';
            return $query->where($field, $value);
        })
        ->when($this->account_id ?? '', function ($query, $value) use ($needsJoin) {
            $field = $needsJoin ? 'packages.account_id' : 'account_id';
            return $query->where($field, $value);
        })
        ->when($this->from_date ?? '', function ($query, $value) use ($needsJoin) {
            $field = $needsJoin ? 'packages.end_date' : 'end_date';
            return $query->where($field, '>=', $value);
        })
        ->when($this->to_date ?? '', function ($query, $value) use ($needsJoin) {
            $field = $needsJoin ? 'packages.start_date' : 'start_date';
            return $query->where($field, '<=', $value);
        });

        // Handle sorting - use packages table prefix for direct fields
        $sortField = $this->sortField;
        if (!str_contains($sortField, '.')) {
            $sortField = ($needsJoin ? 'packages.' : '').$sortField;
        }

        $data = $query->with(['packageCategory', 'account'])
            ->orderBy($sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.package.table', [
            'data' => $data,
        ]);
    }
}
