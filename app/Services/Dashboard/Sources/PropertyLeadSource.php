<?php

namespace App\Services\Dashboard\Sources;

use App\Models\Property;
use App\Models\PropertyLead;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Lead pipeline counts, available units, recent activity, and leads per
 * source / employee. Each keeps the per-block permission it had before.
 * Ported from the Livewire `dashboard.property-lead-dashboard` component.
 */
class PropertyLeadSource extends DashboardSource
{
    /** Leads per employee shows only the busiest few; a long tail of near-zero bars is noise. */
    public const TOP_EMPLOYEES = 15;

    protected const STATUS_TONES = ['New Lead' => 'info', 'Follow Up' => 'warn', 'Closed Deal' => 'ok', 'Visit Scheduled' => 'c2', 'Call Back' => 'c4'];

    public function widgets(): array
    {
        return [
            new WidgetDefinition('lead-status', 'Leads', 'Lead pipeline', 'Leads by stage', 'users', 'kpi', 8, 6, ['property lead.view', 'property lead.dashboard status count']),
            new WidgetDefinition('lead-units', 'Leads', 'Available units', 'By property group', 'home', 'table', 4, 4, ['property lead.view', 'property lead.dashboard status count']),
            new WidgetDefinition('lead-activity', 'Leads', 'Recent lead activity', 'Last updated leads', 'history', 'list', 4, 4, ['property lead.view', 'property lead.dashboard recent activities']),
            new WidgetDefinition('lead-sources', 'Leads', 'Leads per source', 'All leads', 'pie-chart', 'donut', 8, 6, ['property lead.view', 'property lead.dashboard source pie chart']),
            new WidgetDefinition('lead-employees', 'Leads', 'Leads per employee', 'Top 15 by assigned leads', 'bar-chart', 'chart', 12, 6, ['property lead.view', 'property lead.dashboard employee bar chart']),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return match ($key) {
            'lead-units' => ['blocks' => [$this->units()]],
            'lead-activity' => ['blocks' => [$this->activity()], 'link' => $this->link('Open leads', 'property::lead::list', 'property lead.view')],
            'lead-sources' => ['blocks' => [$this->sources()]],
            'lead-employees' => ['blocks' => [$this->employees()]],
            default => ['blocks' => [$this->status()], 'link' => $this->link('Open leads', 'property::lead::list', 'property lead.view')],
        };
    }

    /**
     * @return Builder<PropertyLead>
     */
    protected function leads(): Builder
    {
        return PropertyLead::query()->when(session('branch_id'), fn ($query) => $query->where('branch_id', session('branch_id')));
    }

    /**
     * @return array<string, mixed>
     */
    protected function status(): array
    {
        $stats = $this->leads()
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

        $url = fn (?string $status): ?string => auth()->user()?->can('property lead.view') ? route('property::lead::list', array_filter(['status' => $status])) : null;

        return Blocks::kpis([
            Blocks::kpi('Total leads', (int) $stats?->getAttribute('total_leads'), 'users', url: $url(null)),
            Blocks::kpi('New this week', (int) $stats?->getAttribute('new_deals_week'), 'plus-circle', 'info', url: $url('New Lead')),
            Blocks::kpi('Follow-ups today', (int) $stats?->getAttribute('followups_today'), 'phone', 'warn', url: $url('Follow Up')),
            Blocks::kpi('Closed deals', (int) $stats?->getAttribute('closed_deals'), 'check-circle', 'ok', url: $url('Closed Deal')),
            Blocks::kpi('Visits scheduled', (int) $stats?->getAttribute('visit_scheduled'), 'map-marker', 'c2', url: $url('Visit Scheduled')),
            Blocks::kpi('Call backs', (int) $stats?->getAttribute('callbacks'), 'bell-o', 'c4', url: $url('Call Back')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function units(): array
    {
        $rows = Property::query()
            ->leftJoin('property_groups', 'property_groups.id', '=', 'properties.property_group_id')
            ->when(session('branch_id'), fn ($query) => $query->where('properties.branch_id', session('branch_id')))
            ->where('properties.availability_status', 'available')
            ->groupBy('properties.property_group_id', 'property_groups.name')
            ->select('property_groups.name', DB::raw('COUNT(*) as total'))
            ->orderByDesc('total')
            ->get()
            ->map(fn (Property $row): array => [$row->getAttribute('name') ?? 'No group', Blocks::badge((string) $row->getAttribute('total'), 'ok')])
            ->all();

        return Blocks::table([['label' => 'Property group', 'strong' => true], ['label' => 'Available', 'align' => 'end']], $rows, 'No available units.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function activity(): array
    {
        $canEdit = auth()->user()?->can('property lead.edit');

        return Blocks::list($this->leads()->with('assignee:id,name')->latest('updated_at')->limit(10)->get()->map(fn (PropertyLead $lead): array => [
            'icon' => 'user',
            'tone' => self::STATUS_TONES[$lead->status] ?? 'slate',
            'title' => $lead->name,
            'sub' => ($lead->assignee?->name ?? 'Unassigned').' · '.$lead->updated_at?->diffForHumans(),
            'badge' => ['text' => (string) $lead->status, 'tone' => self::STATUS_TONES[$lead->status] ?? 'slate'],
            'url' => $canEdit ? route('property::lead::edit', $lead->id) : null,
        ])->all(), 'No lead activity yet.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function sources(): array
    {
        $tones = ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9', 'p10', 'p11', 'p12'];
        $sources = $this->leads()->select('source', DB::raw('count(*) as total'))->whereNotNull('source')->groupBy('source')->orderByDesc('total')->get();

        return Blocks::donut(
            $sources->values()->map(fn (PropertyLead $row, int $index): array => ['name' => (string) $row->source, 'value' => (int) $row->getAttribute('total'), 'tone' => $tones[$index % count($tones)]])->all(),
            'number',
            ['value' => (int) $sources->sum('total'), 'format' => 'number', 'label' => 'leads'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function employees(): array
    {
        $employees = $this->leads()
            ->join('users', 'users.id', '=', 'property_leads.assigned_to')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(self::TOP_EMPLOYEES)
            ->get();

        return Blocks::bars($employees->pluck('name')->all(), [Blocks::series('Leads', 'acc', $employees->pluck('total')->all())], 'number', 220);
    }
}
