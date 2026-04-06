<?php

namespace App\Livewire\Dashboard;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenancePriority;
use App\Enums\Maintenance\MaintenanceSegment;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\MaintenanceComplaint;
use App\Models\PropertyGroup;
use Livewire\Component;

class PropertyMaintenanceDashboard extends Component
{
    public int $totalMaintenance = 0;

    public int $completedMaintenance = 0;

    public int $pendingMaintenance = 0;

    public int $cancelledMaintenance = 0;

    public int $inProgressMaintenance = 0;

    public int $outstandingComplaints = 0;

    public int $completedComplaints = 0;

    public int $assignedComplaints = 0;

    public array $priorityBreakdown = [];

    public array $segmentBreakdown = [];

    public array $technicianWorkload = [];

    public array $recentMaintenance = [];

    public array $maintenanceByGroup = [];

    public string $dateFilter = 'month';

    public function mount(): void
    {
        $this->loadData();
    }

    public function setDateFilter(string $filter): void
    {
        $this->dateFilter = $filter;
        $this->loadData();
    }

    private function loadData(): void
    {
        $dateRange = $this->getDateRange();

        $query = Maintenance::query();
        if ($dateRange) {
            $query->whereBetween('date', $dateRange);
        }

        $this->totalMaintenance = (clone $query)->count();
        $this->completedMaintenance = (clone $query)->where('status', MaintenanceStatus::Completed)->count();
        $this->pendingMaintenance = (clone $query)->where('status', MaintenanceStatus::Pending)->count();
        $this->inProgressMaintenance = (clone $query)->where('status', MaintenanceStatus::InProgress)->count();
        $this->cancelledMaintenance = (clone $query)->where('status', MaintenanceStatus::Cancelled)->count();

        $complaintQuery = MaintenanceComplaint::query();
        if ($dateRange) {
            $complaintQuery->whereHas('maintenance', fn ($q) => $q->whereBetween('date', $dateRange));
        }

        $this->outstandingComplaints = (clone $complaintQuery)->where('status', MaintenanceComplaintStatus::Outstanding)->count();
        $this->completedComplaints = (clone $complaintQuery)->where('status', MaintenanceComplaintStatus::Completed)->count();
        $this->assignedComplaints = (clone $complaintQuery)->where('status', MaintenanceComplaintStatus::Assigned)->count();

        // Priority breakdown
        $this->priorityBreakdown = collect(MaintenancePriority::cases())->map(function ($priority) use ($query) {
            return [
                'label' => $priority->label(),
                'color' => $priority->color(),
                'count' => (clone $query)->where('priority', $priority)->count(),
            ];
        })->toArray();

        // Segment breakdown
        $this->segmentBreakdown = collect(MaintenanceSegment::cases())->map(function ($segment) use ($query) {
            return [
                'label' => $segment->label(),
                'color' => $segment->color(),
                'count' => (clone $query)->where('segment', $segment)->count(),
            ];
        })->toArray();

        // Top 5 technician workload
        $this->technicianWorkload = MaintenanceComplaint::query()
            ->whereNotNull('technician_id')
            ->when($dateRange, fn ($q) => $q->whereHas('maintenance', fn ($mq) => $mq->whereBetween('date', $dateRange)))
            ->selectRaw('technician_id, count(*) as total_jobs')
            ->groupBy('technician_id')
            ->orderByDesc('total_jobs')
            ->limit(5)
            ->with('technician:id,name')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->technician?->name ?? 'Unassigned',
                'total' => $item->total_jobs,
                'completed' => MaintenanceComplaint::where('technician_id', $item->technician_id)
                    ->where('status', MaintenanceComplaintStatus::Completed)
                    ->when($dateRange, fn ($q) => $q->whereHas('maintenance', fn ($mq) => $mq->whereBetween('date', $dateRange)))
                    ->count(),
            ])->toArray();

        // Recent maintenance (last 10)
        $this->recentMaintenance = Maintenance::with(['property', 'building', 'customer'])
            ->latest('date')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'date' => $m->date?->format('d M Y'),
                'property' => $m->property?->number ?? '-',
                'building' => $m->building?->name ?? '-',
                'customer' => $m->customer?->name ?? '-',
                'priority' => $m->priority?->label() ?? '-',
                'priority_color' => $m->priority?->color() ?? 'secondary',
                'status' => $m->status?->label() ?? '-',
                'status_color' => $m->status?->color() ?? 'secondary',
            ])->toArray();

        // Maintenance by group (top 6)
        $this->maintenanceByGroup = PropertyGroup::withCount([
            'maintenances' => function ($q) use ($dateRange) {
                if ($dateRange) {
                    $q->whereBetween('date', $dateRange);
                }
            },
        ])
            ->having('maintenances_count', '>', 0)
            ->orderByDesc('maintenances_count')
            ->limit(6)
            ->get()
            ->map(fn ($g) => [
                'name' => $g->name,
                'count' => $g->maintenances_count,
            ])->toArray();
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
        return view('livewire.dashboard.property-maintenance-dashboard');
    }
}
