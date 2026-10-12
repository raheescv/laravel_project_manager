<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenancePriority;
use App\Models\MaintenanceComplaint;
use App\Models\PropertyBuilding;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Illuminate\Support\Collection;

/**
 * Complaint volume and resolution: counts with the overdue alert, complaints
 * per building, the three-month trend and resolution time against target.
 * Ported from the Livewire `dashboard.property-complaint-dashboard` component.
 */
class ComplaintSource extends DashboardSource
{
    /** Target resolution, in days, per maintenance priority. */
    public const TARGET_DAYS = ['critical' => 0.5, 'high' => 1, 'medium' => 3, 'low' => 5];

    public function widgets(): array
    {
        $permission = ['property dashboard.complaint overview'];

        return [
            new WidgetDefinition('complaint-kpis', 'Complaints', 'Complaints', 'Volume & resolution', 'exclamation-triangle', 'kpi', 12, 6, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('complaint-buildings', 'Complaints', 'Complaints by building', 'Top 5 buildings', 'building', 'meter', 4, 4, $permission, self::PERIODS, 'month'),
            new WidgetDefinition('complaint-trend', 'Complaints', 'Monthly trend', 'New vs resolved · 3 months', 'line-chart', 'meter', 4, 4, $permission),
            new WidgetDefinition('complaint-resolution', 'Complaints', 'Resolution time', 'Average vs target', 'clock-o', 'meter', 4, 4, $permission, self::PERIODS, 'month'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $range = $this->periodRange($option);

        return match ($key) {
            'complaint-buildings' => ['blocks' => [$this->buildings($range)]],
            'complaint-trend' => ['blocks' => [$this->trend()]],
            'complaint-resolution' => ['blocks' => $this->resolution($range)],
            default => ['blocks' => $this->status($range)],
        };
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return list<array<string, mixed>>
     */
    protected function status(?array $range): array
    {
        $complaints = MaintenanceComplaint::query()->when($range, fn ($query) => $query->whereBetween('maintenance_complaints.created_at', $range));
        $total = (clone $complaints)->count();
        $pending = (clone $complaints)->where('maintenance_complaints.status', MaintenanceComplaintStatus::Pending)->count();
        $urgent = (clone $complaints)->whereHas('maintenance', fn ($query) => $query->where('priority', MaintenancePriority::Critical))->count();
        $resolved = (clone $complaints)->where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)->count();

        $lastMonth = MaintenanceComplaint::whereBetween('maintenance_complaints.created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();
        $change = $lastMonth > 0 ? round((($total - $lastMonth) / $lastMonth) * 100, 1) : 0.0;

        $overdue = MaintenanceComplaint::whereNotIn('maintenance_complaints.status', [MaintenanceComplaintStatus::Completed, MaintenanceComplaintStatus::Cancelled])
            ->whereHas('maintenance', function ($query): void {
                foreach (self::TARGET_DAYS as $priority => $days) {
                    $query->orWhere(fn ($late) => $late->where('priority', $priority)->where('date', '<', now()->subHours((int) ($days * 24))));
                }
            })->count();

        return array_values(array_filter([
            $overdue > 0 ? Blocks::alert("{$overdue} overdue ".str('complaint')->plural($overdue).' past the target resolution time. Needs attention now.') : null,
            Blocks::kpis([
                Blocks::kpi('Total complaints', $total, 'inbox', delta: ['value' => $change, 'invert' => true, 'hint' => 'vs last month']),
                Blocks::kpi('Pending', $pending, 'clock-o', 'warn', sub: $this->percentage($pending, $total).'% of total'),
                Blocks::kpi('Urgent (critical)', $urgent, 'bolt', 'bad', sub: $this->percentage($urgent, $total).'% of total'),
                Blocks::kpi('Resolved', $resolved, 'check-circle', 'ok', sub: $this->percentage($resolved, $total).'% resolution rate'),
            ]),
        ]));
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return array<string, mixed>
     */
    protected function buildings(?array $range): array
    {
        $rows = MaintenanceComplaint::query()
            ->join('maintenances', 'maintenance_complaints.maintenance_id', '=', 'maintenances.id')
            ->when($range, fn ($query) => $query->whereBetween('maintenance_complaints.created_at', $range))
            ->whereNotNull('maintenances.property_building_id')
            ->whereNull('maintenances.deleted_at')
            ->selectRaw('count(*) as total, maintenances.property_building_id')
            ->groupBy('maintenances.property_building_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $names = PropertyBuilding::whereIn('id', $rows->pluck('property_building_id'))->pluck('name', 'id');
        $max = max(1, (int) $rows->max('total'));

        return Blocks::meters($rows->map(fn (MaintenanceComplaint $row): array => [
            'label' => $names[$row->getAttribute('property_building_id')] ?? 'Unknown',
            'value' => (int) $row->getAttribute('total'),
            'format' => 'number',
            'percent' => $this->percentage((int) $row->getAttribute('total'), $max),
            'tone' => 'bad',
        ])->all(), 'No complaints in this period.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function trend(): array
    {
        return Blocks::meters(collect(range(2, 0))->map(function (int $ago): array {
            $start = now()->subMonths($ago)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $new = MaintenanceComplaint::whereBetween('maintenance_complaints.created_at', [$start, $end])->count();
            $resolved = MaintenanceComplaint::where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)
                ->whereBetween('maintenance_complaints.completed_at', [$start, $end])->count();
            $resolvedShare = min(100, $this->percentage($resolved, max($new, $resolved)));

            return [
                'label' => $start->format('M Y'),
                'text' => "New {$new} · Resolved {$resolved}",
                'stack' => [['percent' => $resolvedShare, 'tone' => 'ok'], ['percent' => $new > 0 ? 100 - $resolvedShare : 0, 'tone' => 'bad']],
            ];
        })->all());
    }

    /**
     * @param  array{0: mixed, 1: mixed}|null  $range
     * @return list<array<string, mixed>>
     */
    protected function resolution(?array $range): array
    {
        $completed = MaintenanceComplaint::with('maintenance:id,priority')
            ->where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)
            ->whereNotNull('maintenance_complaints.completed_at')
            ->when($range, fn ($query) => $query->whereBetween('maintenance_complaints.completed_at', $range))
            ->get(['id', 'maintenance_id', 'created_at', 'completed_at']);

        $average = fn (Collection $rows): float => $rows->isEmpty() ? 0.0 : round($rows->avg(fn (MaintenanceComplaint $complaint): float => $complaint->created_at->diffInHours($complaint->completed_at) / 24), 1);

        return [
            Blocks::stat('Average resolution', $average($completed), 'days'),
            Blocks::meters(collect(MaintenancePriority::cases())->map(function (MaintenancePriority $priority) use ($completed, $average): array {
                $days = $average($completed->filter(fn (MaintenanceComplaint $complaint): bool => $complaint->maintenance?->priority === $priority));
                $target = self::TARGET_DAYS[$priority->value] ?? 5;
                $tone = $days > $target ? 'bad' : 'ok';

                return ['label' => $priority->label(), 'text' => "{$days}d / {$target}d target", 'percent' => min(100, $this->percentage($days, $target)), 'tone' => $tone];
            })->all()),
        ];
    }
}
