<?php

namespace App\Actions\V1\Technician;

use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Http\Resources\V1\Technician\ComplaintListResource;
use App\Models\Maintenance;
use Illuminate\Support\Facades\Auth;

/**
 * Workload summary for the signed-in technician: KPI counts, a priority
 * breakdown of open jobs, and the most recent complaints.
 */
class DashboardAction
{
    use InteractsWithComplaint;

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        $counts = [
            'assigned' => (clone $this->ownedComplaints())->where('status', 'assigned')->count(),
            'pending' => (clone $this->ownedComplaints())->where('status', 'pending')->count(),
            'outstanding' => (clone $this->ownedComplaints())->where('status', 'outstanding')->count(),
            'completed_today' => (clone $this->ownedComplaints())
                ->where('status', 'completed')
                ->where('completed_at', '>=', $today)
                ->count(),
            'completed_week' => (clone $this->ownedComplaints())
                ->where('status', 'completed')
                ->where('completed_at', '>=', $weekStart)
                ->count(),
        ];

        // Priority breakdown across *open* jobs (not completed / cancelled),
        // grouped by the parent maintenance priority.
        $openJobs = (clone $this->ownedComplaints())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('maintenance')
            ->with('maintenance:id,priority,date,time')
            ->get();

        $priorityRows = $openJobs
            ->groupBy(fn ($mc) => $mc->maintenance?->priority?->value ?? 'low')
            ->map->count();

        $priority = [
            'critical' => (int) ($priorityRows['critical'] ?? 0),
            'high' => (int) ($priorityRows['high'] ?? 0),
            'medium' => (int) ($priorityRows['medium'] ?? 0),
            'low' => (int) ($priorityRows['low'] ?? 0),
        ];

        // "Up next": the most urgent open job — highest priority first, then the
        // earliest appointment.
        $severity = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $nextId = $openJobs
            ->sortBy([
                fn ($a, $b) => ($severity[$a->maintenance?->priority?->value ?? 'low'] ?? 3)
                    <=> ($severity[$b->maintenance?->priority?->value ?? 'low'] ?? 3),
                fn ($a, $b) => ($a->maintenance?->date?->timestamp ?? PHP_INT_MAX)
                    <=> ($b->maintenance?->date?->timestamp ?? PHP_INT_MAX),
                fn ($a, $b) => ($a->maintenance?->time ?? '') <=> ($b->maintenance?->time ?? ''),
            ])
            ->first()?->id;

        $next = $nextId === null ? null : $this->ownedComplaints()
            ->with([
                'maintenance.property.building.group',
                'maintenance.customer',
                'maintenance.rentOut.customer',
                'complaint.category',
            ])
            ->find($nextId);

        // Completions per day over the last 7 days (chart series, oldest first).
        $byDay = (clone $this->ownedComplaints())
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $week = collect(range(6, 0))->map(function (int $i) use ($byDay) {
            $day = now()->subDays($i);

            return [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('D'),
                'count' => (int) ($byDay[$day->format('Y-m-d')] ?? 0),
            ];
        })->values();

        $recent = $this->ownedComplaints()
            ->with(['maintenance.property.building.group', 'complaint.category'])
            ->latest('id')
            ->limit(6)
            ->get();

        return [
            'technician' => [
                'id' => (string) Auth::id(),
                'name' => Auth::user()?->name ?? '',
            ],
            'counts' => $counts,
            'priority' => $priority,
            'next' => $next === null ? null : ComplaintListResource::make($next),
            'week' => $week,
            'recent' => ComplaintListResource::collection($recent),
        ];
    }
}
