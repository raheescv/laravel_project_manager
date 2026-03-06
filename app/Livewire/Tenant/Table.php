<?php

namespace App\Livewire\Tenant;

use App\Actions\Tenant\DeleteAction;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

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
            $this->selected = Tenant::latest()->limit(2000)->pluck('id')->toArray();
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

    public function render()
    {
        $data = Tenant::withoutGlobalScopes()
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('subdomain', 'like', "%{$value}%")
                        ->orWhere('domain', 'like', "%{$value}%");
                });
            })
            ->paginate($this->limit);

        return view('livewire.tenant.table', compact('data'));
    }
}
