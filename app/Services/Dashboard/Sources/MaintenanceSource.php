<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenancePriority;
use App\Enums\Maintenance\MaintenanceSegment;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\MaintenanceComplaint;
use App\Models\PropertyGroup;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * Maintenance requests by status, priority and segment, technician
 * workload, requests per group and the latest requests. Ported from the
 * Livewire `dashboard.property-maintenance-dashboard` component.
 */
class MaintenanceSource extends DashboardSource
{
    /** Bootstrap colour names used by the maintenance enums, mapped to dashboard tones. */
    public const TONES = ['primary' => 'acc', 'secondary' => 'slate', 'success' => 'ok', 'info' => 'info', 'warning' => 'warn', 'danger' => 'bad', 'dark' => 'slate', 'light' => 'slate'];

    public function widgets(): array
    {
        $permission = ['property dashboard.maintenance overview'];

        return [
            new WidgetDefinition('maintenance-kpis', 'Maintenance', 'Maintenance', 'Requests by status', 'wrench', 'kpi', 12, 6, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('maintenance-breakdown', 'Maintenance', 'Priority & segment', 'Requests by priority and segment', 'sliders', 'list', 4, 4, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('maintenance-technicians', 'Maintenance', 'Complaints & technicians', 'Complaint status · top technicians', 'user', 'meter', 4, 4, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('maintenance-groups', 'Maintenance', 'Requests by group', 'Top 6 property groups', 'building', 'meter', 4, 4, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('maintenance-recent', 'Maintenance', 'Recent maintenance requests', 'Last 10 requests', 'tasks', 'table', 12, 6, $permission),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $range = $this->periodRange($option);
        $link = $this->link('Open maintenance', 'property::maintenance::index', 'maintenance.view');

        return match ($key) {
            'maintenance-breakdown' => ['blocks' => $this->breakdown($range)],
            'maintenance-technicians' => ['blocks' => $this->technicians($range)],
            'maintenance-groups' => ['blocks' => [$this->groups($range)]],
            'maintenance-recent' => ['blocks' => [$this->recent()], 'link' => $link],
            default => ['blocks' => [$this->status($range)], 'link' => $link],
        };
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return Builder<Maintenance>
     */
    protected function requests(?array $range): Builder
    {
        return Maintenance::query()->when($range, fn ($query) => $query->whereBetween('date', $range));
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return Builder<MaintenanceComplaint>
     */
    protected function complaints(?array $range): Builder
    {
        return MaintenanceComplaint::query()->when($range, fn ($query) => $query->whereHas('maintenance', fn ($maintenance) => $maintenance->whereBetween('date', $range)));
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return array<string, mixed>
     */
    protected function status(?array $range): array
    {
        $requests = $this->requests($range);

        return Blocks::kpis([
            Blocks::kpi('Total requests', (clone $requests)->count(), 'wrench'),
            Blocks::kpi('Pending', (clone $requests)->where('status', MaintenanceStatus::Pending)->count(), 'clock-o', 'warn'),
            Blocks::kpi('In progress', (clone $requests)->where('status', MaintenanceStatus::InProgress)->count(), 'refresh', 'info'),
            Blocks::kpi('Completed', (clone $requests)->where('status', MaintenanceStatus::Completed)->count(), 'check-circle', 'ok'),
        ]);
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return list<array<string, mixed>>
     */
    protected function breakdown(?array $range): array
    {
        $requests = $this->requests($range);

        return [
            Blocks::dots('By priority', collect(MaintenancePriority::cases())->map(fn (MaintenancePriority $priority): array => [
                'label' => $priority->label(),
                'value' => (clone $requests)->where('priority', $priority)->count(),
                'tone' => self::TONES[$priority->color()] ?? 'slate',
            ])->all()),
            Blocks::dots('By segment', collect(MaintenanceSegment::cases())->map(fn (MaintenanceSegment $segment): array => [
                'label' => $segment->label(),
                'value' => (clone $requests)->where('segment', $segment)->count(),
                'tone' => self::TONES[$segment->color()] ?? 'slate',
            ])->all()),
        ];
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return list<array<string, mixed>>
     */
    protected function technicians(?array $range): array
    {
        $complaints = $this->complaints($range);

        $workload = (clone $complaints)
            ->whereNotNull('technician_id')
            ->selectRaw('technician_id, count(*) as total_jobs')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_jobs', [MaintenanceComplaintStatus::Completed->value])
            ->groupBy('technician_id')
            ->orderByDesc('total_jobs')
            ->limit(5)
            ->with('technician:id,name')
            ->get();

        return [
            Blocks::kpis([
                Blocks::kpi('Outstanding', (clone $complaints)->where('status', MaintenanceComplaintStatus::Outstanding)->count(), 'exclamation-circle', 'warn'),
                Blocks::kpi('Assigned', (clone $complaints)->where('status', MaintenanceComplaintStatus::Assigned)->count(), 'user', 'info'),
                Blocks::kpi('Completed', (clone $complaints)->where('status', MaintenanceComplaintStatus::Completed)->count(), 'check-circle', 'ok'),
            ]),
            Blocks::meters($workload->map(function (MaintenanceComplaint $row): array {
                $total = (int) $row->getAttribute('total_jobs');
                $completed = (int) $row->getAttribute('completed_jobs');

                return ['label' => $row->technician->name ?? 'Unassigned', 'text' => "{$completed} / {$total}", 'percent' => $this->percentage($completed, $total), 'tone' => 'ok'];
            })->all(), 'No assigned complaints.'),
        ];
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return array<string, mixed>
     */
    protected function groups(?array $range): array
    {
        $groups = PropertyGroup::withCount(['maintenances' => fn ($query) => $query->when($range, fn ($query) => $query->whereBetween('date', $range))])
            ->having('maintenances_count', '>', 0)
            ->orderByDesc('maintenances_count')
            ->limit(6)
            ->get();
        $max = max(1, (int) $groups->max('maintenances_count'));

        return Blocks::meters($groups->map(fn (PropertyGroup $group): array => [
            'label' => $group->name,
            'value' => (int) $group->maintenances_count,
            'format' => 'number',
            'percent' => $this->percentage((int) $group->maintenances_count, $max),
            'tone' => 'acc',
        ])->all(), 'No requests in this period.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function recent(): array
    {
        $rows = Maintenance::with(['property', 'building', 'customer'])->latest('date')->limit(10)->get()->map(fn (Maintenance $maintenance): array => [
            $maintenance->date->format('d M Y'),
            $maintenance->property->number ?? '-',
            $maintenance->building->name ?? '-',
            $maintenance->customer->name ?? '-',
            Blocks::badge($maintenance->priority->label(), self::TONES[$maintenance->priority->color()] ?? 'slate'),
            Blocks::badge($maintenance->status->label(), self::TONES[$maintenance->status->color()] ?? 'slate'),
        ])->all();

        return Blocks::table([
            ['label' => 'Date'],
            ['label' => 'Property', 'strong' => true],
            ['label' => 'Building'],
            ['label' => 'Customer'],
            ['label' => 'Priority'],
            ['label' => 'Status'],
        ], $rows, 'No maintenance requests yet.');
    }
}
