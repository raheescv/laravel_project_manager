<?php

namespace App\Livewire\Property\Property;

use App\Actions\Property\DeleteAction;
use App\Enums\Property\PropertyStatus;
use App\Models\Property;
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

    public $sortField = 'name';

    public $sortDirection = 'asc';

    public $filterGroup = '';

    public $filterBuilding = '';

    public $filterType = '';

    public $filterStatus = '';

    public $filterAvailabilityStatus = '';

    public $filterFlag = '';

    public $filterOwnership = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Property-Refresh-Component' => '$refresh',
    ];

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
            $this->dispatch('RefreshPropertyTable');
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
            $this->selected = Property::latest()->limit(2000)->pluck('id')->toArray();
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
        $data = Property::with(['building.group', 'type'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('number', 'like', "%{$value}%")
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
            ->when($this->filterStatus, function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->filterAvailabilityStatus, function ($query, $value) {
                return $query->where('availability_status', $value);
            })
            ->when($this->filterFlag, function ($query, $value) {
                return $query->where('flag', $value);
            })
            ->when($this->filterOwnership, function ($query, $value) {
                return $query->where('ownership', $value);
            })
            ->paginate($this->limit);

        return view('livewire.property.property.table', [
            'data' => $data,
        ]);
    }
}
