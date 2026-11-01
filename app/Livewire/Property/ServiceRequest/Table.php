<?php

namespace App\Livewire\Property\ServiceRequest;

use App\Actions\Property\ServiceRequest\DeleteAction;
use App\Enums\Property\ServiceRequestStatus;
use App\Enums\Property\ServiceRequestType;
use App\Models\PropertyServiceRequest;
use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'property_service_requests.id';

    public $sortDirection = 'desc';

    public $filterStatus = '';

    public $filterType = '';

    public $filterUtility = '';

    public $filterServiceStatus = '';

    public $from_date = '';

    public $to_date = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'ServiceRequest-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        abort_unless(auth()->user()?->can('property service request.delete'), 403);
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['selectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->query()->limit(2000)->pluck('property_service_requests.id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    private function query()
    {
        return PropertyServiceRequest::query()
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('remarks', 'like', "%{$value}%")
                        ->orWhereHas('property', fn ($pq) => $pq->where('number', 'like', "%{$value}%"))
                        ->orWhereHas('building', fn ($bq) => $bq->where('name', 'like', "%{$value}%"));
                });
            })
            ->when($this->filterStatus ?? '', fn ($query, $value) => $query->where('status', $value))
            ->when($this->filterType ?? '', fn ($query, $value) => $query->where('type', $value))
            ->when($this->filterUtility ?? '', fn ($query, $value) => $query->where('utility_id', $value))
            ->when($this->filterServiceStatus ?? '', fn ($query, $value) => $query->where('rent_out_service_status', $value))
            ->when($this->from_date ?? '', fn ($query, $value) => $query->where('date', '>=', $value))
            ->when($this->to_date ?? '', fn ($query, $value) => $query->where('date', '<=', $value));
    }

    public function render()
    {
        $data = $this->query()
            ->with(['property', 'building', 'group', 'utility', 'requestedBy', 'approvedBy', 'financialApprovedBy', 'completedBy'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.property.service-request.table', [
            'data' => $data,
            'statuses' => ServiceRequestStatus::cases(),
            'types' => ServiceRequestType::cases(),
            'utilities' => Utility::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
