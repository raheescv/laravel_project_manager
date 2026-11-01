<?php

namespace App\Livewire\Property\ServiceRequest;

use App\Models\PropertyServiceRequest;
use App\Models\Utility;
use Livewire\Component;
use Livewire\WithPagination;

class Report extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 50;

    public $filterUtility = '';

    public $basedOn = 'connect';

    public $onlyConnected = false;

    public $from_date = '';

    public $to_date = '';

    protected $paginationTheme = 'bootstrap';

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    private function query()
    {
        // disconnect_date is a subquery alias, so it can only be filtered in HAVING.
        $byDisconnect = $this->basedOn === 'disconnect';
        $dateFilter = fn ($query, $operator, $value) => $byDisconnect
            ? $query->having('disconnect_date', $operator, $value)
            : $query->where('property_service_requests.date', $operator, $value);

        return PropertyServiceRequest::connectionPeriods()
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->whereHas('property', fn ($pq) => $pq->where('number', 'like', "%{$value}%"))
                        ->orWhereHas('building', fn ($bq) => $bq->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('group', fn ($gq) => $gq->where('name', 'like', "%{$value}%"));
                });
            })
            ->when($this->filterUtility ?? '', fn ($query, $value) => $query->where('property_service_requests.utility_id', $value))
            ->when($this->onlyConnected, fn ($query) => $query->havingNull('disconnect_date'))
            ->when($this->from_date ?? '', fn ($query, $value) => $dateFilter($query, '>=', $value))
            ->when($this->to_date ?? '', fn ($query, $value) => $dateFilter($query, '<=', $value));
    }

    public function render()
    {
        $data = $this->query()
            ->with(['property', 'building', 'group', 'utility'])
            ->orderBy('property_service_requests.date', 'desc')
            ->paginate($this->limit);

        return view('livewire.property.service-request.report', [
            'data' => $data,
            'utilities' => Utility::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
