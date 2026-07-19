<?php

namespace App\Livewire\Property\Property;

use App\Actions\Property\DeleteAction;
use App\Exports\PropertyExport;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'number';

    public $sortDirection = 'asc';

    public $filterGroup = '';

    public $filterBuilding = '';

    public $filterType = '';

    public $filterStatus = '';

    public $filterAvailabilityStatus = '';

    public $filterFlag = '';

    public $filterOwnership = '';

    public $columns = [
        'type' => true,
        'group' => true,
        'building' => true,
        'floor' => true,
        'rent' => true,
        'ownership' => true,
        'kahramaa' => true,
        'parking' => true,
        'status' => true,
        'availability' => true,
    ];

    public $columnLabels = [
        'type' => 'Type',
        'group' => 'Group',
        'building' => 'Building',
        'floor' => 'Floor',
        'rent' => 'Rent',
        'ownership' => 'Ownership',
        'kahramaa' => 'Kahramaa',
        'parking' => 'Parking',
        'status' => 'Status',
        'availability' => 'Availability',
    ];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Property-Refresh-Component' => '$refresh',
    ];

    private function applyFilters($query, bool $includeStatus = true, bool $includeAvailability = true)
    {
        return $query
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('number', 'like', "%{$value}%")
                        ->orWhere('floor', 'like', "%{$value}%")
                        ->orWhereHas('building', function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%");
                        })
                        ->orWhereHas('type', function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%");
                        });
                });
            })
            ->when($this->filterGroup, function ($query, $value) {
                return $query->where('property_group_id', $value);
            })
            ->when($this->filterBuilding, function ($query, $value) {
                return $query->where('property_building_id', $value);
            })
            ->when($this->filterType, function ($query, $value) {
                return $query->where('property_type_id', $value);
            })
            ->when($includeStatus && $this->filterStatus, function ($query) {
                return $query->where('status', $this->filterStatus);
            })
            ->when($includeAvailability && $this->filterAvailabilityStatus, function ($query) {
                return $query->where('availability_status', $this->filterAvailabilityStatus);
            })
            ->when($this->filterFlag, function ($query, $value) {
                return $query->where('flag', $value);
            })
            ->when($this->filterOwnership, function ($query, $value) {
                return $query->where('ownership', $value);
            });
    }

    private function query()
    {
        return $this->applyFilters(Property::with(['building.group', 'type']));
    }

    /**
     * KPI figures for the hero rail. Reflects the active filter context
     * (except Status / Availability, so those breakdowns always stay meaningful).
     */
    private function stats(): array
    {
        $row = $this->applyFilters(Property::query(), includeStatus: false, includeAvailability: false)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant")
            ->selectRaw("SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied")
            ->selectRaw("SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked")
            ->selectRaw("SUM(CASE WHEN availability_status = 'available' THEN 1 ELSE 0 END) as available")
            ->selectRaw("SUM(CASE WHEN availability_status = 'sold' THEN 1 ELSE 0 END) as sold")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'vacant' => (int) ($row->vacant ?? 0),
            'occupied' => (int) ($row->occupied ?? 0),
            'booked' => (int) ($row->booked ?? 0),
            'available' => (int) ($row->available ?? 0),
            'sold' => (int) ($row->sold ?? 0),
        ];
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('property.export'), 403);
        $count = $this->query()->count();
        if ($count === 0) {
            $this->dispatch('error', ['message' => 'No data to export.']);

            return;
        }

        $exportFileName = 'properties_'.now()->timestamp.'.xlsx';

        return Excel::download(new PropertyExport([
            'search' => $this->search,
            'filterGroup' => $this->filterGroup,
            'filterBuilding' => $this->filterBuilding,
            'filterType' => $this->filterType,
            'filterStatus' => $this->filterStatus,
            'filterAvailabilityStatus' => $this->filterAvailabilityStatus,
            'filterFlag' => $this->filterFlag,
            'filterOwnership' => $this->filterOwnership,
        ]), $exportFileName);
    }

    public function delete()
    {
        abort_unless(auth()->user()?->can('property.delete'), 403);
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
            $this->dispatch('RefreshPropertyTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key) && ! preg_match('/^columns\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->query()->limit(2000)->pluck('id')->toArray();
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
        $data = $this->query()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.property.property.table', [
            'data' => $data,
            'stats' => $this->stats(),
        ]);
    }
}
