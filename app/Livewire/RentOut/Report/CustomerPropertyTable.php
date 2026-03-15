<?php

namespace App\Livewire\RentOut\Report;

use App\Models\Account;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use Livewire\Component;

class CustomerPropertyTable extends Component
{
    public $filterCustomer = '';

    public $filterGroup = '';

    public $filterBuilding = '';

    public $filterType = '';

    public $filterProperty = '';

    public $fetched = false;

    public function fetch(): void
    {
        $this->fetched = true;
    }

    public function resetFilters(): void
    {
        $this->filterCustomer = '';
        $this->filterGroup = '';
        $this->filterBuilding = '';
        $this->filterType = '';
        $this->filterProperty = '';
        $this->fetched = false;
        $this->js("
            ['cp_filterCustomer', 'cp_filterGroup', 'cp_filterBuilding', 'cp_filterType', 'cp_filterProperty'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    protected function getFilteredRentOuts()
    {
        return RentOut::query()
            ->with([
                'customer', 'property', 'building', 'group', 'type',
                'rentOutPayments', 'paymentTerms', 'cheques', 'utilityTerms.utility',
            ])
            ->when($this->filterCustomer, fn ($q, $v) => $q->where('account_id', $v))
            ->when($this->filterGroup, fn ($q, $v) => $q->where('property_group_id', $v))
            ->when($this->filterBuilding, fn ($q, $v) => $q->where('property_building_id', $v))
            ->when($this->filterType, fn ($q, $v) => $q->where('property_type_id', $v))
            ->when($this->filterProperty, fn ($q, $v) => $q->where('property_id', $v))
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.rent-out.report.customer-property-table', [
            'rentOuts' => $this->fetched ? $this->getFilteredRentOuts() : collect(),
            'groups' => PropertyGroup::orderBy('name')->pluck('name', 'id'),
            'buildings' => PropertyBuilding::when($this->filterGroup, fn ($q, $v) => $q->where('property_group_id', $v))
                ->orderBy('name')->pluck('name', 'id'),
            'types' => PropertyType::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
