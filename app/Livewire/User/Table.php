<?php

namespace App\Livewire\User;

use App\Models\Branch;
use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 12;

    public $filter = 'date-created';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    /** Facet filters — the columns User::getFilteredQuery() already understands. */
    public $role_id = '';

    public $designation_id = '';

    /** Status facet; starts on active users. */
    public $is_active = '1';

    /** Assigned-branch facet; starts on the session's current branch. */
    public $branch_id = '';

    /** 'list' | 'grid' — remembered for the session so it survives navigation. */
    public $view = 'list';

    protected $listeners = [
        'User-Refresh-Component' => '$refresh',
    ];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->view = session('users.table.view') === 'grid' ? 'grid' : 'list';
        $this->branch_id = (string) (session('branch_id') ?? '');
    }

    public function updated($key, $value): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        switch ($this->filter) {
            case 'date-created':
                $this->sortField = 'created_at';
                $this->sortDirection = 'desc';
                break;
            case 'date-modified':
                $this->sortField = 'updated_at';
                $this->sortDirection = 'desc';
                break;
            case 'alphabetically':
                $this->sortField = 'name';
                $this->sortDirection = 'asc';
                break;
            case 'alphabetically-reversed':
                $this->sortField = 'name';
                $this->sortDirection = 'desc';
                break;
            default:
                $this->sortField = 'id';
                $this->sortDirection = 'asc';
        }
    }

    public function setView($view): void
    {
        $this->view = $view === 'grid' ? 'grid' : 'list';
        session(['users.table.view' => $this->view]);
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
        if ($id !== '' && ! in_array((int) $id, $this->accessibleBranchIds(), true)) {
            return;
        }

        $this->branch_id = (string) $id;
        $this->resetPage();
    }

    /** Back to the default selection: current branch, active only. */
    public function resetFilters(): void
    {
        $this->reset(['search', 'role_id', 'designation_id', 'is_active']);
        $this->branch_id = (string) (session('branch_id') ?? '');
        $this->resetPage();
    }

    /**
     * Current filter set, optionally with some keys removed.
     *
     * Dropping a key is what makes the rail counts behave like real facets: the
     * tally beside "Cashier" is how many users you would get if you clicked it,
     * so its own dimension has to be excluded from the query behind it.
     */
    protected function getFilters(array $except = []): array
    {
        $filters = [
            'type' => 'user',
            'search' => $this->search,
            'role_id' => $this->role_id,
            'designation_id' => $this->designation_id,
            'is_active' => $this->is_active,
            'branch_id' => $this->branch_id,
        ];

        foreach ($except as $key) {
            unset($filters[$key]);
        }

        return $filters;
    }

    /** Users per role, keyed by role id. */
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

    /**
     * Roles worth showing on the rail: those that currently hold a user, plus
     * the selected one so the active facet never disappears.
     *
     * @param  array<int, int>  $roleCounts
     */
    protected function availableRoles(array $roleCounts): Collection
    {
        return Role::forCurrentTenant()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (Role $role): bool => ($roleCounts[$role->id] ?? 0) > 0 || (int) $this->role_id === $role->id)
            ->values();
    }

    /** Users per designation, keyed by designation id. */
    protected function designationCounts(): array
    {
        return User::getFilteredQuery($this->getFilters(['designation_id']))
            ->whereNotNull('users.designation_id')
            ->groupBy('users.designation_id')
            ->selectRaw('users.designation_id as designation_id, count(*) as total')
            ->pluck('total', 'designation_id')
            ->toArray();
    }

    /**
     * Branches the signed-in user may work in — the same list the branch popup offers.
     *
     * @return array<int, int>
     */
    protected function accessibleBranchIds(): array
    {
        return auth()->user()->operableBranchIds();
    }

    /** Users per assigned branch, keyed by branch id. */
    protected function branchCounts(): array
    {
        return User::getFilteredQuery($this->getFilters(['branch_id']))
            ->join('user_has_branches', 'user_has_branches.user_id', '=', 'users.id')
            ->groupBy('user_has_branches.branch_id')
            ->selectRaw('user_has_branches.branch_id as branch_id, count(distinct users.id) as total')
            ->pluck('total', 'branch_id')
            ->toArray();
    }

    public function render()
    {
        $data = User::getFilteredQuery($this->getFilters())
            ->with(['roles:id,name', 'designation:id,name', 'branch:id,name'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $statusCounts = User::getFilteredQuery($this->getFilters(['is_active']))
            ->groupBy('users.is_active')
            ->selectRaw('users.is_active as is_active, count(*) as total')
            ->pluck('total', 'is_active')
            ->toArray();

        $roleCounts = $this->roleCounts();

        return view('livewire.user.table', [
            'data' => $data,
            'roles' => $this->availableRoles($roleCounts),
            'designations' => Designation::orderBy('order_no')->orderBy('name')->get(['id', 'name']),
            'roleCounts' => $roleCounts,
            'designationCounts' => $this->designationCounts(),
            'branches' => Branch::whereIn('id', $this->accessibleBranchIds())->orderBy('name')->pluck('name', 'id')->toArray(),
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
