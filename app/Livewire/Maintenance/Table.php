<?php

namespace App\Livewire\Maintenance;

use App\Actions\Maintenance\DeleteAction;
use App\Enums\Maintenance\MaintenancePriority;
use App\Enums\Maintenance\MaintenanceSegment;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
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

    public $sortField = 'maintenances.id';

    public $sortDirection = 'desc';

    public $filterStatus = '';

    public $filterPriority = '';

    public $filterSegment = '';

    public $filterProperty = '';

    public $filterBuilding = '';

    public $filterGroup = '';

    public $from_date = '';

    public $to_date = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Maintenance-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->from_date = now()->startOfMonth()->format('Y-m-d');
        $this->to_date = now()->format('Y-m-d');
    }

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
            $this->selected = $this->query()->limit(2000)->pluck('maintenances.id')->toArray();
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

    private function query()
    {
        return Maintenance::query()
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('contact_no', 'like', "%{$value}%")
                        ->orWhere('remark', 'like', "%{$value}%")
                        ->orWhereHas('property', function ($pq) use ($value) {
                            $pq->where('name', 'like', "%{$value}%");
                        })
                        ->orWhereHas('customer', function ($cq) use ($value) {
                            $cq->where('name', 'like', "%{$value}%");
                        });
                });
            })
            ->when($this->filterStatus ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->filterPriority ?? '', function ($query, $value) {
                return $query->where('priority', $value);
            })
            ->when($this->filterSegment ?? '', function ($query, $value) {
                return $query->where('segment', $value);
            })
            ->when($this->filterProperty ?? '', function ($query, $value) {
                return $query->where('property_id', $value);
            })
            ->when($this->filterBuilding ?? '', function ($query, $value) {
                return $query->where('property_building_id', $value);
            })
            ->when($this->filterGroup ?? '', function ($query, $value) {
                return $query->where('property_group_id', $value);
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('date', '>=', $value);
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('date', '<=', $value);
            });
    }

    public function render()
    {
        $data = $this->query()
            ->with(['property', 'building', 'customer', 'creator', 'maintenanceComplaints'])
            ->withCount('maintenanceComplaints')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $statuses = MaintenanceStatus::cases();
        $priorities = MaintenancePriority::cases();
        $segments = MaintenanceSegment::cases();

        return view('livewire.maintenance.table', [
            'data' => $data,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'segments' => $segments,
        ]);
    }
}
