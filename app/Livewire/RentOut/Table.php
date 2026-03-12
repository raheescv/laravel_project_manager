<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\DeleteAction;
use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use App\Support\RentOutConfig;
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

    public $sortField = 'id';

    public $sortDirection = 'desc';

    public $statusFilter = '';

    public $agreementType = 'lease';

    protected $paginationTheme = 'bootstrap';

    public function mount($agreementType = 'lease')
    {
        $this->agreementType = $agreementType;
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
            $this->dispatch('success', ['message' => 'Successfully Deleted ' . count($this->selected) . ' items']);
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
                            $q->where('name', 'like', "%{$value}%");
                        });
                });
            })
            ->when($this->statusFilter ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.rent-out.table', [
            'data' => $data,
            'config' => $this->config,
        ]);
    }
}
