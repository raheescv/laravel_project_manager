<?php

namespace App\Livewire\Maintenance;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Models\ComplaintCategory;
use App\Models\MaintenanceComplaint;
use Livewire\Component;
use Livewire\WithPagination;

class Technician extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 15;

    public $sortField = 'maintenance_complaints.id';

    public $sortDirection = 'desc';

    // Filters
    public $filterGroup = '';

    public $filterBuilding = '';

    public $filterCustomer = '';

    public $filterProperty = '';

    public $filterPriority = '';

    public $filterSegment = '';

    public $filterTechnician = '';

    public $filterStatus = '';

    public $filterComplaint = '';

    public $filterCategory = '';

    public $from_date = '';

    public $to_date = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Technician-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->from_date = now()->startOfMonth()->format('Y-m-d');
        $this->to_date = now()->format('Y-m-d');
    }

    public function updated($key, $value)
    {
        if (! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
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

    public function resetFilters()
    {
        $this->reset([
            'search', 'filterGroup', 'filterBuilding', 'filterCustomer', 'filterProperty',
            'filterPriority', 'filterSegment', 'filterTechnician', 'filterStatus',
            'filterComplaint', 'filterCategory',
        ]);
        $this->from_date = now()->startOfMonth()->format('Y-m-d');
        $this->to_date = now()->format('Y-m-d');
        $this->resetPage();
    }

    private function query()
    {
        return MaintenanceComplaint::query()
            ->join('maintenances', 'maintenances.id', '=', 'maintenance_complaints.maintenance_id')
            ->leftJoin('complaints', 'complaints.id', '=', 'maintenance_complaints.complaint_id')
            ->leftJoin('complaint_categories', 'complaint_categories.id', '=', 'complaints.complaint_category_id')
            ->leftJoin('properties', 'properties.id', '=', 'maintenances.property_id')
            ->leftJoin('property_buildings', 'property_buildings.id', '=', 'maintenances.property_building_id')
            ->leftJoin('property_groups', 'property_groups.id', '=', 'maintenances.property_group_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'maintenances.account_id')
            ->leftJoin('users as technicians', 'technicians.id', '=', 'maintenance_complaints.technician_id')
            ->select(
                'maintenance_complaints.*',
                'maintenances.date as maintenance_date',
                'maintenances.time as maintenance_time',
                'maintenances.priority as maintenance_priority',
                'maintenances.segment as maintenance_segment',
                'maintenances.contact_no as maintenance_contact',
                'maintenances.id as registration_id',
                'properties.number as property_number',
                'property_buildings.name as building_name',
                'property_groups.name as group_name',
                'accounts.name as customer_name',
                'complaints.name as complaint_name',
                'complaint_categories.name as category_name',
                'technicians.name as technician_name'
            )
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('properties.number', 'like', "%{$value}%")
                        ->orWhere('property_buildings.name', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%")
                        ->orWhere('complaints.name', 'like', "%{$value}%")
                        ->orWhere('technicians.name', 'like', "%{$value}%")
                        ->orWhere('maintenance_complaints.technician_remark', 'like', "%{$value}%");
                });
            })
            ->when($this->filterGroup ?? '', fn ($q, $v) => $q->where('maintenances.property_group_id', $v))
            ->when($this->filterBuilding ?? '', fn ($q, $v) => $q->where('maintenances.property_building_id', $v))
            ->when($this->filterProperty ?? '', fn ($q, $v) => $q->where('maintenances.property_id', $v))
            ->when($this->filterCustomer ?? '', fn ($q, $v) => $q->where('maintenances.account_id', $v))
            ->when($this->filterPriority ?? '', fn ($q, $v) => $q->where('maintenances.priority', $v))
            ->when($this->filterSegment ?? '', fn ($q, $v) => $q->where('maintenances.segment', $v))
            ->when($this->filterTechnician ?? '', fn ($q, $v) => $q->where('maintenance_complaints.technician_id', $v))
            ->when($this->filterStatus ?? '', fn ($q, $v) => $q->where('maintenance_complaints.status', $v))
            ->when($this->filterComplaint ?? '', fn ($q, $v) => $q->where('maintenance_complaints.complaint_id', $v))
            ->when($this->filterCategory ?? '', fn ($q, $v) => $q->where('complaints.complaint_category_id', $v))
            ->when($this->from_date ?? '', fn ($q, $v) => $q->where('maintenances.date', '>=', $v))
            ->when($this->to_date ?? '', fn ($q, $v) => $q->where('maintenances.date', '<=', $v));
    }

    public function render()
    {
        $data = $this->query()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $statuses = MaintenanceComplaintStatus::cases();
        $categories = ComplaintCategory::orderBy('name')->pluck('name', 'id');

        return view('livewire.maintenance.technician', [
            'data' => $data,
            'statuses' => $statuses,
            'categories' => $categories,
        ]);
    }
}
