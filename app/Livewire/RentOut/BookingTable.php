<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\DeleteAction;
use App\Enums\RentOut\RentOutBookingStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Exports\RentOut\BookingExport;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Support\RentOutConfig;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class BookingTable extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    public $agreementType = 'lease';

    // Filters (TomSelect-driven)
    public $filterGroup = '';

    public $filterBuilding = '';

    public $filterProperty = '';

    public $filterCustomer = '';

    // Date filters
    public $fromDate = '';

    public $toDate = '';

    // Additional filters
    public $filterBookingType = '';

    public $filterStatus = '';

    public $filterBookingStatus = '';

    // Column visibility
    public $columns = [];

    protected $paginationTheme = 'bootstrap';

    protected function getDefaultColumns()
    {
        return [
            'id' => true,
            'customer' => true,
            'group' => true,
            'building' => true,
            'property' => true,
            'start_date' => true,
            'end_date' => true,
            'rent' => true,
            'booking_status' => true,
            'created_at' => false,
        ];
    }

    protected function getConfigKey()
    {
        return 'rent_out_booking_'.$this->agreementType.'_visible_column';
    }

    public function mount($agreementType = 'lease')
    {
        $this->agreementType = $agreementType;
        $config = Configuration::where('key', $this->getConfigKey())->value('value');
        $saved = $config ? json_decode($config, true) : [];
        $this->columns = array_merge($this->getDefaultColumns(), $saved);
    }

    public function toggleColumn($column)
    {
        if (isset($this->columns[$column])) {
            $this->columns[$column] = ! $this->columns[$column];
            Configuration::updateOrCreate(
                ['key' => $this->getConfigKey()],
                ['value' => json_encode($this->columns)]
            );
        }
    }

    public function resetColumns()
    {
        $this->columns = $this->getDefaultColumns();
        Configuration::updateOrCreate(
            ['key' => $this->getConfigKey()],
            ['value' => json_encode($this->columns)]
        );
    }

    public function resetFilters()
    {
        $this->reset([
            'filterGroup', 'filterBuilding', 'filterProperty', 'filterCustomer',
            'filterBookingType', 'filterStatus', 'filterBookingStatus',
            'fromDate', 'toDate',
        ]);
        $this->dispatch('reset-booking-filters');
        $this->resetPage();
    }

    public function getActiveFilterCountProperty(): int
    {
        $count = 0;
        foreach ([
            'filterGroup', 'filterBuilding', 'filterProperty', 'filterCustomer',
            'filterBookingType', 'filterStatus', 'filterBookingStatus',
            'fromDate', 'toDate',
        ] as $filter) {
            if ($this->{$filter} !== '' && $this->{$filter} !== null) {
                $count++;
            }
        }

        return $count;
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    protected function getListeners(): array
    {
        return [
            $this->config->bookingRefreshEvent => '$refresh',
        ];
    }

    public function delete()
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
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
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = RentOut::where('agreement_type', $this->agreementType)
                ->where('status', RentOutStatus::Booked)
                ->latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function download()
    {
        $filters = [
            'agreementType' => $this->agreementType,
            'filterGroup' => $this->filterGroup,
            'filterBuilding' => $this->filterBuilding,
            'filterProperty' => $this->filterProperty,
            'filterCustomer' => $this->filterCustomer,
            'filterBookingType' => $this->filterBookingType,
            'filterBookingStatus' => $this->filterBookingStatus,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'search' => $this->search,
        ];

        return Excel::download(new BookingExport($filters), 'booking-'.$this->agreementType.'-'.now()->format('Y-m-d').'.xlsx');
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

    public function render()
    {
        $data = RentOut::with(['customer', 'property', 'building', 'group'])
            ->where('agreement_type', $this->agreementType)
            ->where('status', RentOutStatus::Booked)
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('id', 'like', "%{$value}%")
                        ->orWhereHas('customer', function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%");
                        })
                        ->orWhereHas('property', function ($q) use ($value) {
                            $q->where('number', 'like', "%{$value}%");
                        });
                });
            })
            ->when($this->filterGroup ?? '', function ($query, $value) {
                return $query->where('property_group_id', $value);
            })
            ->when($this->filterBuilding ?? '', function ($query, $value) {
                return $query->where('property_building_id', $value);
            })
            ->when($this->filterProperty ?? '', function ($query, $value) {
                return $query->where('property_id', $value);
            })
            ->when($this->filterCustomer ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->when($this->filterBookingType ?? '', function ($query, $value) {
                return $query->where('booking_type', $value);
            })
            ->when($this->filterStatus ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->filterBookingStatus ?? '', function ($query, $value) {
                return $query->where('booking_status', $value);
            })
            ->when($this->fromDate ?? '', function ($query, $value) {
                return $query->whereDate('start_date', '>=', $value);
            })
            ->when($this->toDate ?? '', function ($query, $value) {
                return $query->whereDate('end_date', '<=', $value);
            })
            ->latest()
            ->paginate($this->limit);

        $bookingStatusOptions = collect(RentOutBookingStatus::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();

        return view('livewire.rent-out.booking-table', [
            'data' => $data,
            'config' => $this->config,
            'bookingStatusOptions' => $bookingStatusOptions,
        ]);
    }
}
