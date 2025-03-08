<?php

namespace App\Livewire\Settings\Role;

use App\Actions\Settings\Role\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Role-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            @DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                if ($id == 1) {
                    throw new \Exception('You cant delete Super Admin role', 1);
                }
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
            $this->dispatch('RefreshRoleTable');
        } catch (\Exception $e) {
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
            $this->selected = Role::latest()->limit(2000)->pluck('id')->toArray();
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
        $data = Role::orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where('name', 'like', "%{$value}%");
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.settings.role.table', [
            'data' => $data,
        ]);
    }
}
