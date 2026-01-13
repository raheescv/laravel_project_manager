<?php

namespace App\Livewire\User\Employee;

use App\Actions\User\DeleteAction;
use App\Exports\UserExport;
use App\Jobs\Export\ExportUserJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $role_id = '';

    public $is_active = '';

    public $designation_id = '';

    public $sortField = 'users.order_no';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Employee-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                if ($id == 1) {
                    throw new \Exception('Cant Delete The Main Employee', 1);
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
            $this->dispatch('RefreshEmployeeTable');
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
            $this->selected = $this->getBaseQuery()
                ->latest()
                ->limit(2000)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $filters = $this->getFilters();

        $count = $this->getBaseQuery()->count();
        if ($count > 2000) {
            ExportUserJob::dispatch(Auth::user(), $filters);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'Employee_'.now()->timestamp.'.xlsx';

            return Excel::download(new UserExport($filters), $exportFileName);
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

    protected function getFilters(): array
    {
        return [
            'type' => 'employee',
            'search' => $this->search,
            'role_id' => $this->role_id,
            'is_active' => $this->is_active,
            'designation_id' => $this->designation_id,
        ];
    }

    protected function getBaseQuery()
    {
        return User::getFilteredQuery($this->getFilters());
    }

    public function render()
    {
        $data = $this->getBaseQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->leftJoin('designations', 'designations.id', 'users.designation_id')
            ->with(['designation', 'roles'])
            ->select([
                'users.*',
                'designations.name as designation',
            ])
            ->paginate($this->limit);

        $roles = Role::orderBy('name')->get();

        return view('livewire.user.employee.table', [
            'data' => $data,
            'roles' => $roles,
        ]);
    }
}
