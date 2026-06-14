<?php

namespace App\Livewire\FixedAsset;

use App\Actions\Product\DeleteAction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $department_id = '';

    public $main_category_id = '';

    public $brand_id = '';

    public $status = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'FixedAsset-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        abort_unless(auth()->user()?->can('asset.delete'), 403);
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
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Throwable $e) {
            DB::rollBack();
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
        $this->selected = $value ? Product::asset()->latest()->limit(2000)->pluck('id')->toArray() : [];
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
        $query = Product::with(['brand', 'department', 'mainCategory', 'unit'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search, function ($query, $value) {
                $value = trim($value);

                return $query->where(function ($q) use ($value): void {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('item_no', 'like', "%{$value}%")
                        ->orWhere('supplier_name', 'like', "%{$value}%")
                        ->orWhere('location', 'like', "%{$value}%");
                });
            })
            ->when($this->department_id, fn ($query, $value) => $query->where('department_id', $value))
            ->when($this->main_category_id, fn ($query, $value) => $query->where('main_category_id', $value))
            ->when($this->brand_id, fn ($query, $value) => $query->where('brand_id', $value))
            ->when($this->status, fn ($query, $value) => $query->where('status', $value))
            ->asset()
            ->latest();

        $stats = [
            'total_assets' => (clone $query)->count(),
            'active_assets' => (clone $query)->where('status', 'active')->count(),
            'total_purchase_cost' => (clone $query)->sum('cost'),
            'total_depreciation' => (clone $query)->sum('depreciation_amount'),
        ];
        $stats['estimated_book_value'] = max($stats['total_purchase_cost'] - $stats['total_depreciation'], 0);

        $data = $query->paginate($this->limit);

        return view('livewire.fixed-asset.table', ['data' => $data, 'stats' => $stats]);
    }
}
