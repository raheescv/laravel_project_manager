<?php

namespace App\Livewire\Report;

use App\Models\Inventory;
use App\Models\InventoryTransferItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProductReport extends Component
{
    use WithPagination;

    public $search = '';

    public $barcode = '';

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $department_id = '';

    public $main_category_id = '';

    public $limit = 10;

    public $sortField = 'products.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
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

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::query()
            ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id');

        // Get current inventory
        $inventoryQuery = Inventory::select('product_id', DB::raw('SUM(quantity) as current_stock'))
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            })
            ->groupBy('product_id');

        // Get sales count
        $salesQuery = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->when($this->from_date, function ($q) {
                return $q->whereDate('sales.date', '>=', $this->from_date);
            })
            ->when($this->to_date, function ($q) {
                return $q->whereDate('sales.date', '<=', $this->to_date);
            })
            ->when($this->branch_id, function ($q) {
                return $q->where('sales.branch_id', $this->branch_id);
            })
            ->groupBy('product_id');

        // Get purchase count
        $purchaseQuery = PurchaseItem::select('product_id', DB::raw('SUM(quantity) as total_purchased'))
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', 'completed')
            ->when($this->from_date, function ($q) {
                return $q->whereDate('purchases.date', '>=', $this->from_date);
            })
            ->when($this->to_date, function ($q) {
                return $q->whereDate('purchases.date', '<=', $this->to_date);
            })
            ->when($this->branch_id, function ($q) {
                return $q->where('purchases.branch_id', $this->branch_id);
            })
            ->groupBy('product_id');

        $inventoryTransferItemQuery = InventoryTransferItem::query()
            ->join('inventory_transfers', 'inventory_transfers.id', '=', 'inventory_transfer_items.inventory_transfer_id')
            ->where('inventory_transfers.status', 'completed')
            ->when($this->from_date, function ($q) {
                return $q->whereDate('inventory_transfers.date', '>=', $this->from_date);
            })
            ->when($this->to_date, function ($q) {
                return $q->whereDate('inventory_transfers.date', '<=', $this->to_date);
            });
        $transferInQuery = clone $inventoryTransferItemQuery;
        // Get inventory transfer count
        $transferInQuery = $transferInQuery
            ->select('product_id', DB::raw('SUM(quantity) as transfer_in'))
            ->when($this->branch_id, function ($q) {
                return $q->where('inventory_transfers.to_branch_id', $this->branch_id);
            })
            ->groupBy('product_id');

        $transferOutQuery = clone $inventoryTransferItemQuery;
        $transferOutQuery = $transferOutQuery
            ->select('product_id', DB::raw('SUM(quantity) as transfer_out'))
            ->when($this->branch_id, function ($q) {
                return $q->where('inventory_transfers.from_branch_id', $this->branch_id);
            })
            ->groupBy('product_id');

        $products = $query
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.barcode',
                'categories.name as category_name',
                'inventory.current_stock',
                'sales.total_sold',
                'purchases.total_purchased',
                'transfer_in.transfer_in',
                'transfer_out.transfer_out'
            )
            ->when($this->search, function ($q, $value) {
                return $q
                    ->where('products.name', 'like', '%'.trim($value).'%')
                    ->orWhere('products.code', 'like', '%'.trim($value).'%');
            })
            ->when($this->barcode, function ($q, $value) {
                return $q->where('products.barcode', 'like', '%'.trim($value).'%');
            })
            ->when($this->main_category_id, function ($q) {
                return $q->where('products.main_category_id', $this->main_category_id);
            })
            ->product()
            ->leftJoinSub($inventoryQuery, 'inventory', function ($join): void {
                $join->on('products.id', '=', 'inventory.product_id');
            })
            ->leftJoinSub($salesQuery, 'sales', function ($join): void {
                $join->on('products.id', '=', 'sales.product_id');
            })
            ->leftJoinSub($purchaseQuery, 'purchases', function ($join): void {
                $join->on('products.id', '=', 'purchases.product_id');
            })
            ->leftJoinSub($transferInQuery, 'transfer_in', function ($join): void {
                $join->on('products.id', '=', 'transfer_in.product_id');
            })
            ->leftJoinSub($transferOutQuery, 'transfer_out', function ($join): void {
                $join->on('products.id', '=', 'transfer_out.product_id');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.report.product-report', [
            'products' => $products,
        ]);
    }
}
