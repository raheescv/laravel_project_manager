<?php

namespace App\Livewire\Property\PropertyLead;

use App\Actions\Property\PropertyLead\DeleteAction;
use App\Actions\Property\PropertyLead\GetAction;
use App\Exports\PropertyLeadExport;
use App\Models\Country;
use App\Models\PropertyGroup;
use App\Models\PropertyLead;
use App\Support\LeadOptions;
use App\Support\LeadPipeline;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 15;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    // Filters
    /** Pipeline stage key (new, contacting, …) picked from the stage strip. */
    public $filterStage = '';

    public $filterStatus = '';

    public $filterSource = '';

    public $filterSubSource = '';

    public $filterSubStatus = '';

    public $filterType = '';

    public $filterAssignedTo = '';

    public $filterPropertyGroupId = '';

    public $filterLocation = '';

    public $filterCountryId = '';

    /** created | reassigned | updated — which date the from / to filter runs on. */
    public $dateField = 'created';

    public $fromDate;

    public $toDate;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'PropertyLead-Refresh-Component' => '$refresh',
    ];

    public function mount(): void
    {
        $this->fromDate = request('from_date') ?? now()->subMonth()->format('Y-m-d');
        $this->toDate = request('to_date') ?? now()->format('Y-m-d');
        $this->filterStatus = request('status') ?? '';
        $this->dateField = array_key_exists((string) request('date_field'), GetAction::DATE_COLUMNS) ? request('date_field') : 'created';
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->can('property lead.delete'), 403);
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' Lead(s)']);
            $this->selected = [];
            $this->selectAll = false;
            $this->dispatch('PropertyLead-Refresh-Component');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value): void
    {
        // A sub filter only means something under the parent it was picked for.
        if ($key === 'filterSource') {
            $this->filterSubSource = '';
        }
        if ($key === 'filterStatus') {
            $this->filterSubStatus = '';
        }
        if ($key === 'filterStage') {
            $this->filterStatus = '';
            $this->filterSubStatus = '';
        }
        if (! in_array($key, ['selectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->buildQuery()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function pickStage(string $stage): void
    {
        $this->filterStage = $this->filterStage === $stage || ! array_key_exists($stage, LeadPipeline::stages()) ? '' : $stage;
        $this->filterStatus = '';
        $this->filterSubStatus = '';
        $this->resetPage();
    }

    public function pickMatrixCell(string $status, $groupId): void
    {
        $this->filterStage = '';
        $this->filterStatus = $status;
        $this->filterSubStatus = '';
        $this->filterPropertyGroupId = (string) $groupId;
        $this->resetPage();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function sortBy($field): void
    {
        if (! in_array($field, ['id', 'name', 'created_at', 'reassigned_at', 'updated_at'], true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'filterStage', 'filterStatus', 'filterSource', 'filterSubSource', 'filterSubStatus', 'filterType', 'filterAssignedTo',
            'filterPropertyGroupId', 'filterLocation', 'filterCountryId', 'search', 'dateField',
        ]);
        $this->fromDate = now()->subMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('property lead.download'), 403);
        $payload = $this->filterPayload();

        $count = $this->buildQuery()->count();
        if ($count === 0) {
            $this->dispatch('error', ['message' => 'No leads match the current filters.']);

            return;
        }

        $filename = 'property_leads_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new PropertyLeadExport($payload), $filename);
    }

    /**
     * The list filters as GetAction reads them. A stage becomes the exact stored
     * statuses behind it, so drifted spellings ("Low Budget ") still match.
     *
     * @param  list<string>  $except  filter keys to leave out
     * @return array<string, mixed>
     */
    protected function filterPayload(array $except = []): array
    {
        $payload = [
            'status' => $this->filterStatus,
            'source' => $this->filterSource,
            'sub_source' => $this->filterSubSource,
            'sub_status' => $this->filterSubStatus,
            'type' => $this->filterType,
            'assigned_to' => $this->filterAssignedTo,
            'property_group_id' => $this->filterPropertyGroupId,
            'location' => $this->filterLocation,
            'country_id' => $this->filterCountryId,
            'date_field' => $this->dateField,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'search' => $this->search,
        ];

        if (filled($this->filterStage) && ! in_array('stage', $except, true)) {
            $payload['statuses'] = $this->storedStatusesByStage()[$this->filterStage] ?? [];
        }

        return array_diff_key($payload, array_flip($except));
    }

    protected function buildQuery()
    {
        return (new GetAction())->execute($this->filterPayload())['list'];
    }

    /** @return array<string, list<string>> stored status values behind each stage */
    protected function storedStatusesByStage(): array
    {
        $byStage = array_fill_keys(array_keys(LeadPipeline::stages()), []);
        $stored = PropertyLead::query()->toBase()->distinct()->pluck('status');
        foreach ($stored as $status) {
            $stage = LeadPipeline::stageOf(LeadPipeline::canonical($status));
            if ($stage) {
                $byStage[$stage][] = $status;
            }
        }

        return $byStage;
    }

    /**
     * Leads per pipeline stage under every filter except stage and status, so the
     * strip shows where the current slice sits.
     *
     * @return array{stages: array<string, int>, total: int}
     */
    protected function stageCounts(): array
    {
        $rows = (new GetAction())->execute($this->filterPayload(['status', 'sub_status', 'stage']))['list']
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $stages = array_fill_keys(array_keys(LeadPipeline::stages()), 0);
        $total = 0;
        foreach ($rows as $row) {
            $total += (int) $row->total;
            $stage = LeadPipeline::stageOf(LeadPipeline::canonical($row->status));
            if ($stage) {
                $stages[$stage] += (int) $row->total;
            }
        }

        return ['stages' => $stages, 'total' => $total];
    }

    /**
     * Removable chips for each active filter, keyed by the property that clears it.
     *
     * @return array<string, array{label: string, value: string}>
     */
    protected function activeFilters(array $users, array $groups, array $countries): array
    {
        $stages = LeadPipeline::stages();
        $chips = [
            'filterStage' => ['Stage', $stages[$this->filterStage]['name'] ?? null],
            'filterStatus' => ['Status', $this->filterStatus],
            'filterSubStatus' => ['Sub status', $this->filterSubStatus],
            'filterType' => ['Type', leadTypes()[$this->filterType] ?? $this->filterType],
            'filterAssignedTo' => ['Assigned', $users[$this->filterAssignedTo] ?? null],
            'filterSource' => ['Source', $this->filterSource],
            'filterSubSource' => ['Sub source', $this->filterSubSource],
            'filterPropertyGroupId' => ['Project', $groups[$this->filterPropertyGroupId] ?? null],
            'filterCountryId' => ['Nationality', $countries[$this->filterCountryId] ?? null],
            'filterLocation' => ['Location', $this->filterLocation],
            'search' => ['Search', $this->search],
        ];

        return collect($chips)
            ->filter(fn (array $chip) => filled($chip[1]))
            ->map(fn (array $chip) => ['label' => $chip[0], 'value' => (string) $chip[1]])
            ->all();
    }

    /**
     * Sub options to filter by: the configured ones plus any value leads actually
     * carry, narrowed to the chosen parent when there is one.
     *
     * @return array<string, string>
     */
    protected function subOptions(string $key, string $parentColumn, string $column, ?string $parent): array
    {
        $configured = filled($parent)
            ? (LeadOptions::subOptions($key)[$parent] ?? [])
            : array_merge(...array_values(LeadOptions::subOptions($key)) ?: [[]]);

        $stored = PropertyLead::query()
            ->whereNotNull($column)->where($column, '!=', '')
            ->when(filled($parent), fn ($q) => $q->whereRaw("LOWER(TRIM({$parentColumn})) = ?", [mb_strtolower(trim($parent))]))
            ->distinct()->pluck($column)->all();

        $values = array_values(array_unique(array_filter(array_map('trim', [...$configured, ...$stored]), 'filled')));
        natcasesort($values);

        return $values ? array_combine($values, $values) : [];
    }

    public function render()
    {
        $list = $this->buildQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        // Project × status counts, keyed by group then real (canonical) status.
        $statusSummary = [];
        PropertyLead::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->select('property_group_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('property_group_id', 'status')
            ->get()
            ->each(function ($row) use (&$statusSummary): void {
                $status = LeadPipeline::canonical($row->status);
                $statusSummary[$row->property_group_id][$status] = ($statusSummary[$row->property_group_id][$status] ?? 0) + (int) $row->getAttribute('total');
            });

        $groups = PropertyGroup::orderBy('name')->pluck('name', 'id')->toArray();
        $users = LeadOptions::assignees();
        $countries = Country::whereIn('id', PropertyLead::query()->whereNotNull('country_id')->distinct()->select('country_id'))
            ->orderBy('name')->pluck('name', 'id')->toArray();

        return view('livewire.property.property-lead.table', [
            'list' => $list,
            'statuses' => leadStatuses(),
            'stages' => LeadPipeline::stages(),
            'stageCounts' => $this->stageCounts(),
            'activeFilters' => $this->activeFilters($users, $groups, $countries),
            'sources' => leadSources(),
            'types' => leadTypes(),
            'locations' => propertyLeadLocations(),
            'groups' => $groups,
            'users' => $users,
            'statusSummary' => $statusSummary,
            'columns' => collect(ColumnVisibility::current())->filter()->map(fn ($visible, $column) => ColumnVisibility::definitions()[$column]['label'])->all(),
            'subSources' => $this->subOptions(LeadOptions::SUB_SOURCES, 'source', 'sub_source', $this->filterSource),
            'subStatuses' => $this->subOptions(LeadOptions::SUB_STATUSES, 'status', 'sub_status', $this->filterStatus),
            'countries' => $countries,
        ]);
    }
}
