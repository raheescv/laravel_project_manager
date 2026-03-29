<?php

namespace App\Livewire\SupplyRequest;

use App\Models\SupplyRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $status = '';

    public $type = '';

    public ?int $branch_id = null;

    public ?int $property_id = null;

    public ?int $created_by = null;

    public ?int $approved_by = null;

    public ?int $final_approved_by = null;

    public $from_date = '';

    public $to_date = '';

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'supply_requests.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount($type = ''): void
    {
        $this->type = $type;
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
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

    public function updated($key, $value): void
    {
        if (in_array($key, ['search', 'status', 'type', 'branch_id', 'property_id', 'created_by', 'approved_by', 'final_approved_by', 'from_date', 'to_date', 'limit'])) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = SupplyRequest::when($this->type, fn ($q) => $q->where('type', $this->type))
                ->latest()->limit(500)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function delete(): void
    {
        try {
            DB::beginTransaction();
            if (empty($this->selected)) {
                throw new \Exception('Please select items to delete');
            }
            SupplyRequest::whereIn('id', $this->selected)->delete();
            $this->dispatch('success', ['message' => 'Successfully deleted '.count($this->selected).' items']);
            DB::commit();
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $data = SupplyRequest::with(['property', 'branch', 'creator', 'approver', 'finalApprover', 'completer'])
            ->withCount('items')
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->filter([
                'search' => $this->search,
                'status' => $this->status,
                'branch_id' => $this->branch_id,
                'property_id' => $this->property_id,
                'created_by' => $this->created_by,
                'approved_by' => $this->approved_by,
                'final_approved_by' => $this->final_approved_by,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
            ])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.supply-request.table', [
            'data' => $data,
        ]);
    }
}
