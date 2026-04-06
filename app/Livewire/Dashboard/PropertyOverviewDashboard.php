<?php

namespace App\Livewire\Dashboard;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\RentOutPaymentTerm;
use Livewire\Component;

class PropertyOverviewDashboard extends Component
{
    public int $totalProperties = 0;

    public int $totalBuildings = 0;

    public int $vacantProperties = 0;

    public int $occupiedProperties = 0;

    public array $groupOccupancyRates = [];

    public array $groupIncomeReports = [];

    public array $totalIncomeData = [];

    public string $incomeFilter = 'month';

    public function mount(): void
    {
        $this->loadPropertyStats();
        $this->loadGroupOccupancyRates();
        $this->calculateGroupIncomeReports();
    }

    public function setIncomeFilter(string $filter): void
    {
        $this->incomeFilter = $filter;
        $this->calculateGroupIncomeReports();
    }

    private function loadPropertyStats(): void
    {
        $this->totalProperties = Property::count();
        $this->totalBuildings = PropertyBuilding::count();
        $this->vacantProperties = Property::where('status', PropertyStatus::Vacant)->count();
        $this->occupiedProperties = Property::where('status', PropertyStatus::Occupied)->count();
    }

    private function loadGroupOccupancyRates(): void
    {
        $groups = PropertyGroup::withCount([
            'properties',
            'properties as vacant_count' => fn ($q) => $q->where('status', PropertyStatus::Vacant),
            'properties as occupied_count' => fn ($q) => $q->where('status', PropertyStatus::Occupied),
        ])->get();

        $this->groupOccupancyRates = $groups->map(function ($group) {
            $total = $group->properties_count;
            $occupied = $group->occupied_count;
            $rate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

            return [
                'name' => $group->name,
                'total' => $total,
                'vacant' => $group->vacant_count,
                'occupied' => $occupied,
                'rate' => $rate,
            ];
        })->toArray();
    }

    private function calculateGroupIncomeReports(): void
    {
        $dateRange = $this->getDateRange();
        $groups = PropertyGroup::all();

        $this->groupIncomeReports = $groups->map(function ($group) use ($dateRange) {
            $terms = RentOutPaymentTerm::whereHas('rentOut', function ($q) use ($group) {
                $q->where('property_group_id', $group->id)
                    ->where('status', RentOutStatus::Occupied);
            });

            if ($dateRange) {
                $terms = $terms->whereBetween('due_date', $dateRange);
            }

            $collection = $terms->sum('total');
            $paid = (clone $terms)->sum('paid');
            $pending = $collection - $paid;
            $collectionRate = $collection > 0 ? round(($paid / $collection) * 100, 1) : 0;

            return [
                'name' => $group->name,
                'collection' => $collection,
                'paid' => $paid,
                'pending' => $pending,
                'rate' => $collectionRate,
            ];
        })->toArray();

        $this->totalIncomeData = [
            'collection' => array_sum(array_column($this->groupIncomeReports, 'collection')),
            'paid' => array_sum(array_column($this->groupIncomeReports, 'paid')),
            'pending' => array_sum(array_column($this->groupIncomeReports, 'pending')),
            'rate' => 0,
        ];

        if ($this->totalIncomeData['collection'] > 0) {
            $this->totalIncomeData['rate'] = round(
                ($this->totalIncomeData['paid'] / $this->totalIncomeData['collection']) * 100, 1
            );
        }
    }

    private function getDateRange(): ?array
    {
        return match ($this->incomeFilter) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.dashboard.property-overview-dashboard');
    }
}
