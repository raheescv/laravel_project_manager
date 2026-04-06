<?php

namespace App\Livewire\Dashboard;

use App\Models\Property;
use App\Models\PropertyLead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PropertyLeadDashboard extends Component
{
    public int $totalLeads = 0;

    public int $followUpsToday = 0;

    public int $newDealsThisWeek = 0;

    public int $closedDeals = 0;

    public int $totalVisitScheduled = 0;

    public int $totalCallBack = 0;

    public array $availableUnits = [];

    public Collection $recentLeadUpdates;

    public array $sourceLabels = [];

    public array $sourceData = [];

    public array $employeeLabels = [];

    public array $employeeData = [];

    public function mount(): void
    {
        $this->recentLeadUpdates = collect();
        $this->loadDashboardStats();
        $this->loadChartData();
    }

    protected function baseQuery()
    {
        return PropertyLead::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')));
    }

    protected function loadDashboardStats(): void
    {
        $stats = $this->baseQuery()
            ->select([
                DB::raw('COUNT(*) as total_leads'),
                DB::raw("SUM(CASE WHEN status = 'Follow Up' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as followups_today"),
                DB::raw("SUM(CASE WHEN status = 'New Lead' AND created_at >= ? THEN 1 ELSE 0 END) as new_deals_week"),
                DB::raw("SUM(CASE WHEN status = 'Closed Deal' THEN 1 ELSE 0 END) as closed_deals"),
                DB::raw("SUM(CASE WHEN status = 'Visit Scheduled' THEN 1 ELSE 0 END) as visit_scheduled"),
                DB::raw("SUM(CASE WHEN status = 'Call Back' THEN 1 ELSE 0 END) as callbacks"),
            ])
            ->addBinding([now()->subWeek()->toDateTimeString()], 'select')
            ->first();

        $this->totalLeads = (int) ($stats->total_leads ?? 0);
        $this->followUpsToday = (int) ($stats->followups_today ?? 0);
        $this->newDealsThisWeek = (int) ($stats->new_deals_week ?? 0);
        $this->closedDeals = (int) ($stats->closed_deals ?? 0);
        $this->totalVisitScheduled = (int) ($stats->visit_scheduled ?? 0);
        $this->totalCallBack = (int) ($stats->callbacks ?? 0);

        // Available units grouped by property_group
        $this->availableUnits = Property::query()
            ->leftJoin('property_buildings', 'property_buildings.id', '=', 'properties.property_building_id')
            ->leftJoin('property_groups', 'property_groups.id', '=', 'properties.property_group_id')
            ->when(session('branch_id'), fn ($q) => $q->where('properties.branch_id', session('branch_id')))
            ->where('properties.availability_status', 'available')
            ->groupBy('properties.property_group_id', 'property_groups.name')
            ->select('property_groups.name', 'properties.property_group_id as id', DB::raw('COUNT(*) as total'))
            ->get()
            ->toArray();

        $this->recentLeadUpdates = $this->baseQuery()
            ->with('assignee:id,name')
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    protected function loadChartData(): void
    {
        $sources = $this->baseQuery()
            ->select('source', DB::raw('count(*) as total'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        $this->sourceLabels = $sources->pluck('source')->toArray();
        $this->sourceData = $sources->pluck('total')->map(fn ($v) => (int) $v)->toArray();

        $employees = $this->baseQuery()
            ->join('users', 'users.id', '=', 'property_leads.assigned_to')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get();

        $this->employeeLabels = $employees->pluck('name')->toArray();
        $this->employeeData = $employees->pluck('total')->map(fn ($v) => (int) $v)->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.property-lead-dashboard');
    }
}
