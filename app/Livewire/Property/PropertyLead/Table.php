<?php

namespace App\Livewire\Property\PropertyLead;

use App\Actions\Property\PropertyLead\DeleteAction;
use App\Actions\Property\PropertyLead\GetAction;
use App\Exports\PropertyLeadExport;
use App\Models\PropertyGroup;
use App\Models\PropertyLead;
use App\Models\User;
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
    public $filterStatus = '';

    public $filterSource = '';

    public $filterType = '';

    public $filterAssignedTo = '';

    public $filterPropertyGroupId = '';

    public $filterLocation = '';

    public $filterCountryId = '';

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
    }

    public function delete(): void
    {
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' Lead(s)']);
            DB::commit();
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

    public function sortBy($field): void
    {
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
            'filterStatus', 'filterSource', 'filterType', 'filterAssignedTo',
            'filterPropertyGroupId', 'filterLocation', 'filterCountryId', 'search',
        ]);
        $this->fromDate = now()->subMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function export()
    {
        $payload = [
            'status' => $this->filterStatus,
            'source' => $this->filterSource,
            'type' => $this->filterType,
            'assigned_to' => $this->filterAssignedTo,
            'property_group_id' => $this->filterPropertyGroupId,
            'location' => $this->filterLocation,
            'country_id' => $this->filterCountryId,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'search' => $this->search,
        ];

        $count = $this->buildQuery()->count();
        if ($count === 0) {
            $this->dispatch('error', ['message' => 'No leads match the current filters.']);

            return;
        }

        $filename = 'property_leads_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new PropertyLeadExport($payload), $filename);
    }

    protected function buildQuery()
    {
        $payload = [
            'status' => $this->filterStatus,
            'source' => $this->filterSource,
            'type' => $this->filterType,
            'assigned_to' => $this->filterAssignedTo,
            'property_group_id' => $this->filterPropertyGroupId,
            'location' => $this->filterLocation,
            'country_id' => $this->filterCountryId,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'search' => $this->search,
        ];

        return (new GetAction())->execute($payload)['list'];
    }

    public function render()
    {
        $list = $this->buildQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        // Status summary by group
        $statusSummary = PropertyLead::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->select('property_group_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('property_group_id', 'status')
            ->get()
            ->groupBy('property_group_id');

        return view('livewire.property.property-lead.table', [
            'list' => $list,
            'statuses' => leadStatuses(),
            'sources' => leadSources(),
            'types' => leadTypes(),
            'locations' => propertyLeadLocations(),
            'groups' => PropertyGroup::orderBy('name')->pluck('name', 'id')->toArray(),
            'users' => User::orderBy('name')->pluck('name', 'id')->toArray(),
            'statusSummary' => $statusSummary,
        ]);
    }
}
