<?php

namespace App\Livewire\Dashboard;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\RentOutPaymentTerm;
use Carbon\Carbon;
use Livewire\Component;

class PropertyOverviewDashboard extends Component
{
    public int $totalProperties = 0;

    public int $totalBuildings = 0;

    public int $vacantProperties = 0;

    public int $occupiedProperties = 0;

    public array $groupOccupancyRates = [];

    public array $groupAvailabilityRates = [];

    public array $groupIncomeReports = [];

    public array $totalIncomeData = [];

    public string $incomeFilter = 'month';

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function setIncomeFilter(string $filter): void
    {
        $this->incomeFilter = $filter;
        $this->calculateGroupIncomeReports();
        $this->calculateTotalIncomeData();
    }

    private function loadDashboard(): void
    {
        $this->loadPropertyStats();
        $this->loadGroupOccupancyRates();
        $this->loadGroupAvailabilityRates();
        $this->calculateGroupIncomeReports();
        $this->calculateTotalIncomeData();
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
            'properties as vacant_count' => fn ($query) => $query->where('status', PropertyStatus::Vacant),
            'properties as occupied_count' => fn ($query) => $query->where('status', PropertyStatus::Occupied),
        ])->orderBy('name')->get();

        $this->groupOccupancyRates = $groups->map(function ($group): array {
            $total = (int) $group->properties_count;
            $occupied = (int) $group->occupied_count;
            $vacant = (int) $group->vacant_count;
            $rate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

            return [
                'id' => $group->id,
                'name' => $group->name,
                'total' => $total,
                'vacant' => $vacant,
                'occupied' => $occupied,
                'rate' => $rate,
            ];
        })->toArray();
    }

    private function loadGroupAvailabilityRates(): void
    {
        $groups = PropertyGroup::withCount([
            'properties',
            'properties as available_count' => fn ($query) => $query->where('availability_status', 'available'),
            'properties as sold_count' => fn ($query) => $query->where('availability_status', 'sold'),
        ])->orderBy('name')->get();

        $this->groupAvailabilityRates = $groups->map(function ($group): array {
            $total = (int) $group->properties_count;
            $available = (int) $group->available_count;
            $sold = (int) $group->sold_count;
            $availableRate = $total > 0 ? round(($available / $total) * 100, 1) : 0;
            $soldRate = $total > 0 ? round(($sold / $total) * 100, 1) : 0;

            return [
                'id' => $group->id,
                'name' => $group->name,
                'total' => $total,
                'available' => $available,
                'sold' => $sold,
                'available_rate' => $availableRate,
                'sold_rate' => $soldRate,
            ];
        })->toArray();
    }

    private function calculateGroupIncomeReports(): void
    {
        $dateRange = $this->getDateRange();
        $groups = PropertyGroup::withCount('properties')->orderBy('name')->get();

        $this->groupIncomeReports = $groups->map(function ($group) use ($dateRange): array {
            $baseTerms = RentOutPaymentTerm::query()
                ->whereHas('rentOut', function ($query) use ($group): void {
                    $query->where('property_group_id', $group->id)
                        ->where('status', '!=', RentOutStatus::Cancelled->value);
                });

            $filteredTerms = clone $baseTerms;
            if ($dateRange !== null) {
                $filteredTerms->whereBetween('due_date', $dateRange);
            }

            $totalIncome = (float) (clone $baseTerms)->sum('paid');
            $periodIncome = (float) (clone $filteredTerms)->sum('paid');
            $collection = (float) (clone $filteredTerms)->sum('total');
            $paid = (float) (clone $filteredTerms)->sum('paid');
            $pendingPayments = (float) (clone $filteredTerms)->where('balance', '>', 0)->sum('balance');
            $overdueAmount = (float) (clone $filteredTerms)
                ->where('balance', '>', 0)
                ->whereDate('due_date', '<', now()->toDateString())
                ->sum('balance');

            $collectionRate = $collection > 0 ? round(($paid / $collection) * 100, 1) : 0;

            return [
                'id' => $group->id,
                'name' => $group->name,
                'total_income' => $totalIncome,
                'period_income' => $periodIncome,
                'monthly_income' => $this->getMonthlyIncome($group->id),
                'yearly_income' => $this->getYearlyIncome($group->id),
                'overdue_amount' => $overdueAmount,
                'collection_rate' => $collectionRate,
                'total_units' => (int) $group->properties_count,
                'pending_payments' => $pendingPayments,
            ];
        })->filter(fn (array $group): bool => $group['total_units'] > 0)->values()->toArray();
    }

    private function getMonthlyIncome(int $groupId): float
    {
        return (float) RentOutPaymentTerm::query()
            ->whereHas('rentOut', function ($query) use ($groupId): void {
                $query->where('property_group_id', $groupId)
                    ->where('status', '!=', RentOutStatus::Cancelled->value);
            })
            ->whereBetween('due_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('paid');
    }

    private function getYearlyIncome(int $groupId): float
    {
        return (float) RentOutPaymentTerm::query()
            ->whereHas('rentOut', function ($query) use ($groupId): void {
                $query->where('property_group_id', $groupId)
                    ->where('status', '!=', RentOutStatus::Cancelled->value);
            })
            ->whereBetween('due_date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])
            ->sum('paid');
    }

    private function calculateTotalIncomeData(): void
    {
        $baseTerms = RentOutPaymentTerm::query()
            ->whereHas('rentOut', function ($query): void {
                $query->where('status', '!=', RentOutStatus::Cancelled->value);
            });

        $filteredTerms = clone $baseTerms;
        $dateRange = $this->getDateRange();

        if ($dateRange !== null) {
            $filteredTerms->whereBetween('due_date', $dateRange);
        }

        $totalCollected = (float) (clone $filteredTerms)->sum('paid');
        $totalExpected = (float) (clone $filteredTerms)->sum('total');
        $totalPending = (float) (clone $filteredTerms)->where('balance', '>', 0)->sum('balance');
        $totalOverdue = (float) (clone $filteredTerms)
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString())
            ->sum('balance');
        $overallCollectionRate = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 1) : 0;

        $this->totalIncomeData = [
            'total_collected' => $totalCollected,
            'total_pending' => $totalPending,
            'total_overdue' => $totalOverdue,
            'overall_collection_rate' => $overallCollectionRate,
        ];
    }

    private function getDateRange(): ?array
    {
        return match ($this->incomeFilter) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'all' => null,
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.dashboard.property-overview-dashboard');
    }
}
