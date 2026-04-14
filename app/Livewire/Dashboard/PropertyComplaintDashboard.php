<?php

namespace App\Livewire\Dashboard;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenancePriority;
use App\Models\MaintenanceComplaint;
use App\Models\PropertyBuilding;
use Livewire\Component;

class PropertyComplaintDashboard extends Component
{
    public int $totalComplaints = 0;

    public int $pendingComplaints = 0;

    public int $urgentComplaints = 0;

    public int $resolvedComplaints = 0;

    public int $overdueCount = 0;

    public float $pendingPercentage = 0;

    public float $urgentPercentage = 0;

    public float $resolvedPercentage = 0;

    public float $totalComplaintsChange = 0;

    public float $averageResolutionTime = 0;

    public float $averageResolutionChange = 0;

    public array $complaintsByBuilding = [];

    public array $monthlyTrend = [];

    public array $resolutionByPriority = [];

    public string $dateFilter = 'month';

    private array $targetResolutionDays = [
        'critical' => 0.5,
        'high' => 1,
        'medium' => 3,
        'low' => 5,
    ];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function setDateFilter(string $filter): void
    {
        $this->dateFilter = $filter;
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $dateRange = $this->getDateRange();

        $query = MaintenanceComplaint::query();
        if ($dateRange) {
            $query->whereBetween('maintenance_complaints.created_at', $dateRange);
        }

        $this->totalComplaints = (clone $query)->count();
        $this->pendingComplaints = (clone $query)->where('maintenance_complaints.status', MaintenanceComplaintStatus::Pending)->count();
        $this->urgentComplaints = (clone $query)->whereHas('maintenance', fn ($q) => $q->where('priority', MaintenancePriority::Critical))->count();
        $this->resolvedComplaints = (clone $query)->where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)->count();

        $this->pendingPercentage = $this->totalComplaints > 0 ? round(($this->pendingComplaints / $this->totalComplaints) * 100, 1) : 0;
        $this->urgentPercentage = $this->totalComplaints > 0 ? round(($this->urgentComplaints / $this->totalComplaints) * 100, 1) : 0;
        $this->resolvedPercentage = $this->totalComplaints > 0 ? round(($this->resolvedComplaints / $this->totalComplaints) * 100, 1) : 0;

        // Month-over-month change
        $lastMonthCount = MaintenanceComplaint::whereBetween('maintenance_complaints.created_at', [
            now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth(),
        ])->count();
        $this->totalComplaintsChange = $lastMonthCount > 0
            ? round((($this->totalComplaints - $lastMonthCount) / $lastMonthCount) * 100, 1) : 0;

        // Overdue complaints
        $this->overdueCount = MaintenanceComplaint::where('maintenance_complaints.status', '!=', MaintenanceComplaintStatus::Completed)
            ->where('maintenance_complaints.status', '!=', MaintenanceComplaintStatus::Cancelled)
            ->whereHas('maintenance', function ($q) {
                foreach ($this->targetResolutionDays as $priority => $days) {
                    $q->orWhere(function ($subQ) use ($priority, $days) {
                        $subQ->where('priority', $priority)
                            ->where('date', '<', now()->subDays($days));
                    });
                }
            })->count();

        // Complaints by building (top 5)
        $this->complaintsByBuilding = MaintenanceComplaint::query()
            ->join('maintenances', 'maintenance_complaints.maintenance_id', '=', 'maintenances.id')
            ->when($dateRange, fn ($q) => $q->whereBetween('maintenance_complaints.created_at', $dateRange))
            ->whereNotNull('maintenances.property_building_id')
            ->where('maintenances.deleted_at', null)
            ->selectRaw('count(*) as total, maintenances.property_building_id')
            ->groupBy('maintenances.property_building_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $building = PropertyBuilding::find($item->property_building_id);

                return [
                    'name' => $building?->name ?? 'Unknown',
                    'count' => $item->total,
                ];
            })->toArray();

        // Monthly trend (last 3 months)
        $this->monthlyTrend = collect(range(2, 0))->map(function ($monthsAgo) {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end = now()->subMonths($monthsAgo)->endOfMonth();

            return [
                'month' => $start->format('M Y'),
                'new' => MaintenanceComplaint::whereBetween('maintenance_complaints.created_at', [$start, $end])->count(),
                'resolved' => MaintenanceComplaint::where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)
                    ->whereBetween('maintenance_complaints.completed_at', [$start, $end])->count(),
            ];
        })->toArray();

        // Average resolution time
        $completedComplaints = MaintenanceComplaint::where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)
            ->whereNotNull('maintenance_complaints.completed_at')
            ->when($dateRange, fn ($q) => $q->whereBetween('maintenance_complaints.completed_at', $dateRange))
            ->get();

        if ($completedComplaints->isNotEmpty()) {
            $totalDays = $completedComplaints->sum(fn ($c) => $c->created_at->diffInHours($c->completed_at) / 24);
            $this->averageResolutionTime = round($totalDays / $completedComplaints->count(), 1);
        }

        // Resolution time by priority
        $this->resolutionByPriority = collect(MaintenancePriority::cases())->map(function ($priority) use ($dateRange) {
            $completed = MaintenanceComplaint::where('maintenance_complaints.status', MaintenanceComplaintStatus::Completed)
                ->whereNotNull('maintenance_complaints.completed_at')
                ->whereHas('maintenance', fn ($q) => $q->where('priority', $priority))
                ->when($dateRange, fn ($q) => $q->whereBetween('maintenance_complaints.completed_at', $dateRange))
                ->get();

            $avgDays = 0;
            if ($completed->isNotEmpty()) {
                $totalDays = $completed->sum(fn ($c) => $c->created_at->diffInHours($c->completed_at) / 24);
                $avgDays = round($totalDays / $completed->count(), 1);
            }

            return [
                'label' => $priority->label(),
                'color' => $priority->color(),
                'avg_days' => $avgDays,
                'target' => $this->targetResolutionDays[$priority->value] ?? 5,
            ];
        })->toArray();
    }

    private function getDateRange(): ?array
    {
        return match ($this->dateFilter) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.dashboard.property-complaint-dashboard');
    }
}
