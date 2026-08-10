<?php

namespace App\Livewire\User;

use App\Models\Designation;
use App\Models\User;
use App\Services\TenantService;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

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

    public $is_active = '';

    /** 'list' | 'grid' — remembered for the session so it survives navigation. */
    public $view = 'list';

    protected $listeners = [
        'User-Refresh-Component' => '$refresh',
    ];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->view = session('users.table.view') === 'grid' ? 'grid' : 'list';
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

    public function resetFilters(): void
    {
        $this->reset(['search', 'role_id', 'designation_id', 'is_active']);
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

    public function render()
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();

        $data = User::getFilteredQuery($this->getFilters())
            ->with(['roles:id,name', 'designation:id,name', 'branch:id,name'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $statusCounts = User::getFilteredQuery($this->getFilters(['is_active']))
            ->groupBy('users.is_active')
            ->selectRaw('users.is_active as is_active, count(*) as total')
            ->pluck('total', 'is_active')
            ->toArray();

        return view('livewire.user.table', [
            'data' => $data,
            'roles' => Role::query()
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'designations' => Designation::orderBy('order_no')->orderBy('name')->get(['id', 'name']),
            'roleCounts' => $this->roleCounts(),
            'designationCounts' => $this->designationCounts(),
            'statusCounts' => [
                'active' => (int) ($statusCounts[1] ?? 0),
                'inactive' => (int) ($statusCounts[0] ?? 0),
            ],
            'allRolesCount' => User::getFilteredQuery($this->getFilters(['role_id']))->count(),
            'allDesignationsCount' => User::getFilteredQuery($this->getFilters(['designation_id']))->count(),
        ]);
    }
}
