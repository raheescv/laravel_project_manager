<?php

namespace App\Livewire\SupplyRequest;

use App\Models\Configuration;
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

    public ?int $property_group_id = null;

    public ?int $property_building_id = null;

    public ?int $property_type_id = null;

    public ?int $created_by = null;

    public ?int $approved_by = null;

    public ?int $final_approved_by = null;

    public $from_date = '';

    public $to_date = '';

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'supply_requests.id';

    public $sortDirection = 'desc';

    public $visibleColumns = [];

    protected $paginationTheme = 'bootstrap';

    protected function getDefaultColumns(): array
    {
        return [
            'id' => true,
            'date' => true,
            'order_no' => true,
            'type' => true,
            'property' => true,
            'property_group' => false,
            'property_building' => false,
            'property_type' => false,
            'requested_by' => true,
            'items' => true,
            'grand_total' => true,
            'status' => true,
            'created_by' => true,
            'approved_by' => false,
            'created_at' => true,
        ];
    }

    public function mount($type = ''): void
    {
        $this->type = $type;
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');

        $config = Configuration::where('key', 'supply_request_table_visible_columns')->value('value');
        $savedColumns = $config ? json_decode($config, true) : [];
        $this->visibleColumns = array_merge($this->getDefaultColumns(), $savedColumns);
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
        if (str_starts_with($key, 'visibleColumns.')) {
            Configuration::updateOrCreate(
                ['key' => 'supply_request_table_visible_columns'],
                ['value' => json_encode($this->visibleColumns)]
            );
        } elseif (in_array($key, ['search', 'status', 'type', 'branch_id', 'property_id', 'property_group_id', 'property_building_id', 'property_type_id', 'created_by', 'approved_by', 'final_approved_by', 'from_date', 'to_date', 'limit'])) {
            $this->resetPage();
        }
    }

    public function resetColumnVisibility(): void
    {
        $this->visibleColumns = $this->getDefaultColumns();
        Configuration::updateOrCreate(
            ['key' => 'supply_request_table_visible_columns'],
            ['value' => json_encode($this->visibleColumns)]
        );
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
        $data = SupplyRequest::with(['property.group', 'property.building', 'property.type', 'branch', 'creator', 'approver', 'finalApprover', 'completer'])
            ->withCount('items')
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->filter([
                'search' => $this->search,
                'status' => $this->status,
                'branch_id' => $this->branch_id,
                'property_id' => $this->property_id,
                'property_group_id' => $this->property_group_id,
                'property_building_id' => $this->property_building_id,
                'property_type_id' => $this->property_type_id,
                'created_by' => $this->created_by,
                'approved_by' => $this->approved_by,
                'final_approved_by' => $this->final_approved_by,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
            ])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $visibleColumnNames = [
            'id' => '#',
            'date' => 'Date',
            'order_no' => 'Order No',
            'type' => 'Type',
            'property' => 'Property',
            'property_group' => 'Property Group',
            'property_building' => 'Building',
            'property_type' => 'Property Type',
            'requested_by' => 'Requested By',
            'items' => 'Items',
            'grand_total' => 'Grand Total',
            'status' => 'Status',
            'created_by' => 'Created By',
            'approved_by' => 'Approved By',
            'created_at' => 'Created At',
        ];

        return view('livewire.supply-request.table', [
            'data' => $data,
            'visibleColumnNames' => $visibleColumnNames,
        ]);
    }
}
