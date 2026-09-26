<?php

namespace App\Livewire\Tenant;

use App\Actions\Tenant\DeleteAction;
use App\Models\Configuration;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    /** all | active | inactive | trashed */
    public $status = 'all';

    /** An active_module value, or '' for every system. */
    public $system = '';

    public $limit = 10;

    public $filter = 'date-created';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $selected = [];

    public $selectAll = false;

    protected $listeners = [
        'Tenant-Refresh-Component' => '$refresh',
    ];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Check if user is super admin
        if (! Auth::user()->is_super_admin) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
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
            $this->selected = $this->filteredQuery()->whereKeyNot(app(TenantService::class)->getCurrentTenantId())->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedFilter()
    {
        switch ($this->filter) {
            case 'date-created':
                $this->sortField = 'created_at';
                $this->sortDirection = 'asc';
                break;
            case 'date-modified':
                $this->sortField = 'updated_at';
                $this->sortDirection = 'asc';
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

    public function delete()
    {
        abort_unless(Auth::user()?->is_super_admin, 403, 'Unauthorized access. Only super admin users can perform this action.');
        try {
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
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];
            $this->selectAll = false;
            $this->dispatch('RefreshTenantTable');
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'system');
    }

    /**
     * The tenant list shared by the table and the status counts. $except drops
     * one filter so each status chip counts across the others.
     */
    private function filteredQuery(array $except = [])
    {
        $systemTenantIds = $this->system
            ? Configuration::withoutGlobalScopes()->where('key', 'active_module')->where('value', $this->system)->pluck('tenant_id')
            : null;

        return Tenant::query()
            ->when(! in_array('status', $except, true), fn ($query) => match ($this->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'trashed' => $query->onlyTrashed(),
                default => $query,
            })
            ->when($systemTenantIds, fn ($query, $ids) => $query->whereIn('id', $ids))
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('subdomain', 'like', "%{$value}%")
                        ->orWhere('domain', 'like', "%{$value}%");
                });
            });
    }

    public function render()
    {
        $unscoped = fn ($query) => $query->withoutGlobalScopes();

        $data = $this->filteredQuery()
            ->withCount(['users' => $unscoped, 'branches' => $unscoped, 'products' => fn ($query) => $query->withoutGlobalScopes()->whereNull('products.deleted_at')])
            ->withMax(['sales' => $unscoped], 'created_at')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $counts = $this->filteredQuery(['status'])->withTrashed()
            ->selectRaw('SUM(deleted_at IS NULL) as all_count')
            ->selectRaw('SUM(deleted_at IS NULL AND is_active = 1) as active_count')
            ->selectRaw('SUM(deleted_at IS NULL AND is_active = 0) as inactive_count')
            ->selectRaw('SUM(deleted_at IS NOT NULL) as trashed_count')
            ->first();

        $systemsByTenant = Configuration::withoutGlobalScopes()->where('key', 'active_module')
            ->whereIn('tenant_id', $data->pluck('id'))->pluck('value', 'tenant_id');

        return view('livewire.tenant.table', [
            'data' => $data,
            'counts' => [
                'all' => (int) $counts->all_count,
                'active' => (int) $counts->active_count,
                'inactive' => (int) $counts->inactive_count,
                'trashed' => (int) $counts->trashed_count,
            ],
            'systemsByTenant' => $systemsByTenant,
            'systems' => array_keys(config('modules.systems', [])),
            'currentTenantId' => app(TenantService::class)->getCurrentTenantId(),
        ]);
    }
}
