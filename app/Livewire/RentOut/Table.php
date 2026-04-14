<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\DeleteAction;
use App\Exports\RentOut\RentOutTableExport;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Support\RentOutConfig;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
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

    public $filterStatus = '';

    // Date filters
    public $fromDate = '';

    public $toDate = '';

    // Utility filters
    public $electricityFilter = '';

    public $acFilter = '';

    public $wifiFilter = '';

    // Column visibility
    public $columns = [];

    protected $paginationTheme = 'bootstrap';

    protected function getDefaultColumns()
    {
        return [
            'id' => true,
            'customer' => true,
            'property' => true,
            'building' => true,
            'start_date' => true,
            'end_date' => true,
            'rent' => true,
            'status' => true,
        ];
    }

    protected function getConfigKey()
    {
        return 'rent_out_'.$this->agreementType.'_visible_column';
    }

    public function mount($agreementType = 'lease')
    {
        $this->agreementType = $agreementType;
        $config = Configuration::where('key', $this->getConfigKey())->value('value');
        $this->columns = $config ? json_decode($config, true) : $this->getDefaultColumns();
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
            'filterStatus', 'fromDate', 'toDate', 'electricityFilter', 'acFilter', 'wifiFilter',
        ]);
        $this->dispatch('reset-rent-out-filters');
        $this->resetPage();
    }

    public function getActiveFilterCountProperty(): int
    {
        $count = 0;
        foreach (['filterGroup', 'filterBuilding', 'filterProperty', 'filterCustomer', 'filterStatus', 'fromDate', 'toDate'] as $filter) {
            if ($this->{$filter} !== '' && $this->{$filter} !== null) {
                $count++;
            }
        }
        foreach (['electricityFilter', 'acFilter', 'wifiFilter'] as $filter) {
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
            $this->config->refreshEvent => '$refresh',
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
            $this->dispatch($this->config->refreshTableEvent);
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
            'filterStatus' => $this->filterStatus,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'electricityFilter' => $this->electricityFilter,
            'acFilter' => $this->acFilter,
            'wifiFilter' => $this->wifiFilter,
            'search' => $this->search,
        ];

        return Excel::download(new RentOutTableExport($filters), 'rent-out-'.$this->agreementType.'-'.now()->format('Y-m-d').'.xlsx');
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
        $data = RentOut::with(['customer', 'property', 'building'])
            ->where('agreement_type', $this->agreementType)
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
            ->when($this->filterStatus ?? '', function ($query, $value) {
                return $query->where('status', $value);
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
            ->when($this->fromDate ?? '', function ($query, $value) {
                return $query->whereDate('start_date', '>=', $value);
            })
            ->when($this->toDate ?? '', function ($query, $value) {
                return $query->whereDate('end_date', '<=', $value);
            })
            ->when($this->electricityFilter !== '' && $this->electricityFilter !== null ? true : false, function ($query) {
                return $query->where('include_electricity_water', $this->electricityFilter);
            })
            ->when($this->acFilter !== '' && $this->acFilter !== null ? true : false, function ($query) {
                return $query->where('include_ac', $this->acFilter);
            })
            ->when($this->wifiFilter !== '' && $this->wifiFilter !== null ? true : false, function ($query) {
                return $query->where('include_wifi', $this->wifiFilter);
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.rent-out.table', [
            'data' => $data,
            'config' => $this->config,
        ]);
    }
}
