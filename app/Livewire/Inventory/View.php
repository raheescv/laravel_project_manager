<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use WithPagination;

    public $product;

    public $search = '';

    public $log_search = '';

    public $product_id = '';

    public $branch_id = '';

    public $sortField = 'inventory_logs.id';

    public $sortDirection = 'desc';

    public $chartView = 'daily';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Inventory-Refresh-Component' => '$refresh',
    ];

    public function mount($product_id)
    {
        $this->product_id = $product_id;
        $this->branch_id = session('branch_id');
        $this->product = Product::with('brand')->find($this->product_id);
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

    public function toggleChartView()
    {
        $this->chartView = $this->chartView === 'monthly' ? 'daily' : 'monthly';
        $this->dispatch('propertyUpdated', $this->chartView);
    }

    public function render()
    {
        $data = Inventory::join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%")
                        ->orWhere('inventories.quantity', 'like', "%{$value}%")
                        ->orWhere('inventories.cost', 'like', "%{$value}%")
                        ->orWhere('branches.name', 'like', "%{$value}%");
                });
            })
            // ->when($this->branch_id ?? '', function ($query, $value) {
            //     return $query->where('branch_id', $value);
            // })
            ->where('product_id', $this->product_id)
            ->latest('inventories.created_at')
            ->select(
                'inventories.id',
                'inventories.branch_id',
                'inventories.barcode',
                'inventories.batch',
                'inventories.cost',
                'inventories.quantity',
                'inventories.total',
            )
            ->get();
        $logs = InventoryLog::with('branch')->orderBy($this->sortField, $this->sortDirection)
            ->when($this->log_search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('inventory_logs.remarks', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->where('product_id', $this->product_id)
            ->latest()
            ->paginate();

        $start = date('Y-m-d', strtotime('-12 months'));
        $end = date('Y-m-d');
        $monthly_summary = InventoryLog::monthly_summary($start, $end, $this->product_id);

        $start = date('Y-m-d', strtotime('-30 days'));
        $daily_summary = InventoryLog::daily_summary($start, $end, $this->product_id);

        return view('livewire.inventory.view', [
            'data' => $data,
            'monthly_summary' => $monthly_summary,
            'daily_summary' => $daily_summary,
            'logs' => $logs,
        ]);
    }
}
