<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\RentOutPaymentTerm;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * Occupancy, per-group occupancy and availability, and rent collection by
 * group and in total. Ported from the Livewire
 * `dashboard.property-overview-dashboard` component.
 */
class PropertyOverviewSource extends DashboardSource
{
    public function widgets(): array
    {
        $permission = ['property.dashboard overview'];

        return [
            new WidgetDefinition('property-occupancy', 'Property', 'Occupancy', 'All buildings', 'building', 'kpi', 12, 6, $permission),
            new WidgetDefinition('group-occupancy', 'Property', 'Group occupancy', 'Occupied of total units', 'th-large', 'meter', 6, 4, $permission),
            new WidgetDefinition('availability', 'Property', 'Availability snapshot', 'Units available · sold', 'th', 'meter', 6, 4, $permission),
            new WidgetDefinition('group-income', 'Property', 'Group income', 'Rent collection by group', 'money', 'table', 8, 6, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('income-summary', 'Property', 'Income summary', 'Rent collection', 'dollar', 'kpi', 4, 4, $permission, self::PERIODS, 'month'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return match ($key) {
            'group-occupancy' => ['blocks' => [$this->groupOccupancy()]],
            'availability' => ['blocks' => [$this->availability()]],
            'group-income' => ['blocks' => [$this->groupIncome((string) $option)], 'link' => $this->link('Open rent payments', 'property::rent::payments', 'rent out.payment')],
            'income-summary' => ['blocks' => [$this->incomeSummary((string) $option)]],
            default => ['blocks' => $this->occupancy(), 'link' => $this->link('Open units', 'property::property::index', 'property.view')],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function occupancy(): array
    {
        $total = Property::count();
        $occupied = Property::where('status', PropertyStatus::Occupied)->count();
        $vacant = Property::where('status', PropertyStatus::Vacant)->count();
        $rate = $this->percentage($occupied, $total);
        $user = auth()->user();
        $units = $user?->can('property.view');

        return [
            Blocks::progress('Overall occupancy', $rate, $this->rateTone($rate), "{$occupied} occupied · {$vacant} vacant"),
            Blocks::kpis([
                $user?->can('property building.view') ? Blocks::kpi('Buildings', PropertyBuilding::count(), 'building', url: route('property::building::index')) : null,
                $units ? Blocks::kpi('Units', $total, 'th', url: route('property::property::index')) : null,
                $units ? Blocks::kpi('Occupied', $occupied, 'key', 'ok', url: route('property::property::index', ['status' => 'occupied'])) : null,
                $units ? Blocks::kpi('Vacant', $vacant, 'home', 'warn', url: route('property::property::index', ['status' => 'vacant'])) : null,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function groupOccupancy(): array
    {
        $groups = PropertyGroup::withCount([
            'properties',
            'properties as occupied_count' => fn ($query) => $query->where('status', PropertyStatus::Occupied),
        ])->orderBy('name')->get();

        return Blocks::meters($groups->filter(fn (PropertyGroup $group): bool => $group->properties_count > 0)->map(function (PropertyGroup $group): array {
            $occupied = (int) $group->getAttribute('occupied_count');
            $rate = $this->percentage($occupied, (int) $group->properties_count);

            return ['label' => $group->name, 'text' => "{$occupied} / {$group->properties_count}", 'badge' => ['text' => $rate.'%', 'tone' => $this->rateTone($rate)], 'percent' => $rate, 'tone' => $this->rateTone($rate)];
        })->values()->all(), 'No property groups yet.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function availability(): array
    {
        $groups = PropertyGroup::withCount([
            'properties',
            'properties as available_count' => fn ($query) => $query->where('availability_status', 'available'),
            'properties as sold_count' => fn ($query) => $query->where('availability_status', 'sold'),
        ])->orderBy('name')->get();

        return Blocks::meters($groups->filter(fn (PropertyGroup $group): bool => $group->properties_count > 0)->map(function (PropertyGroup $group): array {
            $available = (int) $group->getAttribute('available_count');
            $sold = (int) $group->getAttribute('sold_count');
            $rate = $this->percentage($available, (int) $group->properties_count);

            return ['label' => $group->name, 'text' => "{$available} available · {$sold} sold", 'badge' => ['text' => $rate.'%', 'tone' => 'info'], 'percent' => $rate, 'tone' => 'info'];
        })->values()->all(), 'No property groups yet.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function groupIncome(string $period): array
    {
        $range = $this->periodRange($period);
        $canPay = auth()->user()?->can('rent out.payment');

        $rows = PropertyGroup::withCount('properties')->orderBy('name')->get()
            ->filter(fn (PropertyGroup $group): bool => $group->properties_count > 0)
            ->map(function (PropertyGroup $group) use ($range, $canPay): array {
                $terms = $this->terms($range)->whereHas('rentOut', fn ($query) => $query->where('property_group_id', $group->id));
                $expected = (float) (clone $terms)->sum('total');
                $collected = (float) (clone $terms)->sum('paid');
                $pending = (float) (clone $terms)->where('balance', '>', 0)->sum('balance');
                $overdue = (float) (clone $terms)->where('balance', '>', 0)->whereDate('due_date', '<', today())->sum('balance');
                $rate = $this->percentage($collected, $expected);

                return [
                    $canPay ? ['text' => $group->name, 'url' => route('property::rent::payments', ['property_group_id' => $group->id])] : $group->name,
                    $group->properties_count,
                    $collected,
                    $pending,
                    ['value' => $overdue, 'format' => 'money', 'tone' => $overdue > 0 ? 'bad' : null],
                    ['bar' => ['percent' => $rate, 'tone' => $this->rateTone($rate, 80, 65)]],
                ];
            })->values()->all();

        return Blocks::table([
            ['label' => 'Group', 'strong' => true],
            ['label' => 'Units', 'align' => 'end', 'format' => 'number'],
            ['label' => 'Collected', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Pending', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Overdue', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Rate'],
        ], $rows, 'No rent due in this period.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function incomeSummary(string $period): array
    {
        $terms = $this->terms($this->periodRange($period));
        $collected = (float) (clone $terms)->sum('paid');
        $expected = (float) (clone $terms)->sum('total');

        return Blocks::kpis([
            Blocks::kpi('Collected', $collected, 'check', 'ok', 'money'),
            Blocks::kpi('Pending', (float) (clone $terms)->where('balance', '>', 0)->sum('balance'), 'clock-o', 'warn', 'money'),
            Blocks::kpi('Overdue', (float) (clone $terms)->where('balance', '>', 0)->whereDate('due_date', '<', today())->sum('balance'), 'exclamation-circle', 'bad', 'money'),
            Blocks::kpi('Collection rate', $this->percentage($collected, $expected), 'line-chart', 'acc', 'percent'),
        ]);
    }

    /**
     * Payment terms of agreements that are not cancelled, due in the range.
     *
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return Builder<RentOutPaymentTerm>
     */
    protected function terms(?array $range): Builder
    {
        return RentOutPaymentTerm::query()
            ->whereHas('rentOut', fn ($query) => $query->where('status', '!=', RentOutStatus::Cancelled->value))
            ->when($range, fn ($query) => $query->whereBetween('due_date', $range));
    }
}
