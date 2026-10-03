<?php

namespace App\Livewire\User\Employee;

use App\Actions\User\BranchAction;
use App\Actions\User\DeleteAction;
use App\Exports\UserExport;
use App\Jobs\Export\ExportUserJob;
use App\Models\Branch;
use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 12;

    public $selected = [];

    public $selectAll = false;

    public $role_id = '';

    public $is_active = '';

    public $designation_id = '';

    public $branch_id = '';

    /** Bulk "assign branches" modal state. */
    public $bulk_branch_ids = [];

    public $bulk_default_branch_id = '';

    public $bulk_branch_mode = BranchAction::MODE_REPLACE;

    public $sortField = 'users.order_no';

    public $sortDirection = 'asc';

    /** Toolbar sort preset; each maps to a sortField / sortDirection pair. */
    public $filter = 'order';

    /** 'list' | 'grid' — remembered for the session so it survives navigation. */
    public $view = 'list';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Employee-Refresh-Component' => '$refresh',
    ];

    public function mount(): void
    {
        $this->view = session('employees.table.view') === 'grid' ? 'grid' : 'list';
    }

    public function delete()
    {
        abort_unless(auth()->user()?->can('employee.delete'), 403);
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
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
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

    public function openBranchModal()
    {
        abort_unless(auth()->user()?->can('employee.edit'), 403);
        if (! count($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select any item to assign branches.']);

            return;
        }
        $this->dispatch('OpenEmployeeBranchModal');
    }

    public function assignBranches()
    {
        abort_unless(auth()->user()?->can('employee.edit'), 403);
        try {
            DB::beginTransaction();
            $response = (new BranchAction())->bulk($this->selected, $this->bulk_branch_ids, $this->bulk_branch_mode, $this->bulk_default_branch_id);
            if (! $response['result']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->reset(['bulk_branch_ids', 'bulk_default_branch_id', 'bulk_branch_mode']);
            $this->selected = [];
            $this->selectAll = false;
            $this->dispatch('CloseEmployeeBranchModal');
            $this->dispatch('RefreshEmployeeTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        // Selection and the bulk-assign modal's own fields must not bounce the
        // table back to page 1 while the user is mid-action.
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^(selected\.|bulk_).*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedFilter(): void
    {
        [$this->sortField, $this->sortDirection] = match ($this->filter) {
            'alphabetically' => ['users.name', 'asc'],
            'alphabetically-reversed' => ['users.name', 'desc'],
            'date-created' => ['users.created_at', 'desc'],
            'date-modified' => ['users.updated_at', 'desc'],
            'code' => ['users.code', 'asc'],
            default => ['users.order_no', 'asc'],
        };
    }

    public function setView($view): void
    {
        $this->view = $view === 'grid' ? 'grid' : 'list';
        session(['employees.table.view' => $this->view]);
    }

    public function setRole($id): void
    {
        $this->role_id = (string) $id;
        $this->resetPage();
    }

    public function setDesignation($id): void
    {
        $this->designation_id = (string) $id;
        $this->resetPage();
    }

    public function setStatus($value): void
    {
        $this->is_active = (string) $value;
        $this->resetPage();
    }

    public function setBranch($id): void
    {
        $this->branch_id = (string) $id;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'role_id', 'designation_id', 'is_active', 'branch_id']);
        $this->resetPage();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
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
        abort_unless(auth()->user()?->can('employee.export'), 403);
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

    /**
     * Current filter set, optionally with some keys removed.
     *
     * Dropping a key is what makes the rail counts behave like real facets: the
     * tally beside "Cashier" is how many employees you would get if you clicked
     * it, so its own dimension has to be excluded from the query behind it.
     */
    protected function getFilters(array $except = []): array
    {
        $filters = [
            'type' => 'employee',
            'search' => $this->search,
            'role_id' => $this->role_id,
            'is_active' => $this->is_active,
            'designation_id' => $this->designation_id,
            'branch_id' => $this->branch_id,
        ];

        foreach ($except as $key) {
            unset($filters[$key]);
        }

        return $filters;
    }

    /** Employees per role, keyed by role id. */
    protected function roleCounts(): array
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');

        return User::getFilteredQuery($this->getFilters(['role_id']))
            ->join($table, function ($join) use ($table, $morphKey): void {
                $join->on($table.'.'.$morphKey, '=', 'users.id')
                    ->where($table.'.model_type', '=', (new User())->getMorphClass());
            })
            ->groupBy($table.'.role_id')
            ->selectRaw($table.'.role_id as role_id, count(distinct users.id) as total')
            ->pluck('total', 'role_id')
            ->toArray();
    }

    /** Employees per designation, keyed by designation id. */
    protected function designationCounts(): array
    {
        return User::getFilteredQuery($this->getFilters(['designation_id']))
            ->whereNotNull('users.designation_id')
            ->groupBy('users.designation_id')
            ->selectRaw('users.designation_id as designation_id, count(*) as total')
            ->pluck('total', 'designation_id')
            ->toArray();
    }

    /** Employees per assigned branch, keyed by branch id. */
    protected function branchCounts(): array
    {
        return User::getFilteredQuery($this->getFilters(['branch_id']))
            ->join('user_has_branches', 'user_has_branches.user_id', '=', 'users.id')
            ->groupBy('user_has_branches.branch_id')
            ->selectRaw('user_has_branches.branch_id as branch_id, count(distinct users.id) as total')
            ->pluck('total', 'branch_id')
            ->toArray();
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
            ->with(['designation', 'roles', 'branches'])
            ->select([
                'users.*',
                'designations.name as designation',
            ])
            ->paginate($this->limit);

        $statusCounts = User::getFilteredQuery($this->getFilters(['is_active']))
            ->groupBy('users.is_active')
            ->selectRaw('users.is_active as is_active, count(*) as total')
            ->pluck('total', 'is_active')
            ->toArray();

        return view('livewire.user.employee.table', [
            'data' => $data,
            'roles' => Role::forCurrentTenant()->orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('order_no')->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('name')->pluck('name', 'id')->toArray(),
            'roleCounts' => $this->roleCounts(),
            'designationCounts' => $this->designationCounts(),
            'branchCounts' => $this->branchCounts(),
            'statusCounts' => [
                'active' => (int) ($statusCounts[1] ?? 0),
                'inactive' => (int) ($statusCounts[0] ?? 0),
            ],
            'allRolesCount' => User::getFilteredQuery($this->getFilters(['role_id']))->count(),
            'allDesignationsCount' => User::getFilteredQuery($this->getFilters(['designation_id']))->count(),
            'allBranchesCount' => User::getFilteredQuery($this->getFilters(['branch_id']))->count(),
        ]);
    }
}
