<?php

namespace App\Livewire\Report;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockAnalysisReport extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $branch_id;

    public $report_type = 'top_moving'; // non_moving, top_moving

    public $days_threshold = 30; // Number of days to consider for non-moving items

    public $limit = 10; // Number of top moving products to show

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d', strtotime('-30 days'));
        $this->to_date = date('Y-m-d');
    }

    public function updated($key, $value)
    {
        $this->resetPage();
        if (in_array($key, ['from_date', 'to_date', 'branch_id', 'limit', 'report_type'])) {
            if ($this->report_type === 'top_moving') {
                $this->dispatch('updateChart', $this->getChartData());
            }
        }
    }

    protected function getNonMovingProducts()
    {
        $query = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('branches', 'inventories.branch_id', '=', 'branches.id')
            ->leftJoin(DB::raw('(
                SELECT product_id, branch_id, MAX(created_at) as last_movement
                FROM inventory_logs
                WHERE (quantity_in > 0 OR quantity_out > 0)
                GROUP BY product_id, branch_id
            ) as last_movements'), function ($join) {
                $join->on('inventories.product_id', '=', 'last_movements.product_id')
                    ->on('inventories.branch_id', '=', 'last_movements.branch_id');
            })
            ->where('products.type', 'product')
            ->where('inventories.quantity', '>', 0)
            ->when($this->branch_id, function ($q) {
                return $q->where('inventories.branch_id', $this->branch_id);
            });

        // If last movement is null or older than threshold days
        $query->where(function ($q) {
            $q->whereNull('last_movements.last_movement')
                ->orWhere('last_movements.last_movement', '<=', now()->subDays(intval($this->days_threshold)));
        });

        return $query->select(
            'products.id',
            'products.name',
            'products.code',
            'inventories.quantity',
            'inventories.cost',
            'branches.name as branch_name',
            'last_movements.last_movement',
            DB::raw('inventories.quantity * inventories.cost as stock_value')
        )->orderBy('last_movements.last_movement', 'asc');
    }

    protected function getTopMovingProducts()
    {
        return InventoryLog::query()
            ->join('products', 'inventory_logs.product_id', '=', 'products.id')
            ->join('branches', 'inventory_logs.branch_id', '=', 'branches.id')
            ->whereBetween('inventory_logs.created_at', [
                Carbon::parse($this->from_date)->startOfDay(),
                Carbon::parse($this->to_date)->endOfDay(),
            ])
            ->when($this->branch_id, function ($q) {
                return $q->where('inventory_logs.branch_id', $this->branch_id);
            })
            ->where('products.type', 'product')
            ->limit(10)
            ->groupBy('products.id', 'products.name', 'products.code', 'branches.name')
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'branches.name as branch_name',
                DB::raw('SUM(quantity_out) as total_quantity_out'),
                DB::raw('SUM(quantity_in) as total_quantity_in')
            )
            ->orderBy('total_quantity_out', 'desc');
    }

    public function getChartData()
    {
        $data = $this->getTopMovingProducts()
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('name')->toArray(),
            'datasets' => [
                [
                    'data' => $data->pluck('total_quantity_out')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    ],
                ],
            ],
        ];
    }

    public function render()
    {
        $branches = Branch::orderBy('name')->pluck('name', 'id');

        $products = $this->report_type === 'non_moving'
            ? $this->getNonMovingProducts()->paginate(10)
            : $this->getTopMovingProducts()->limit($this->limit)->get();

        $chartData = $this->report_type === 'top_moving' ? $this->getChartData() : null;

        return view('livewire.report.stock-analysis-report', compact('products', 'branches', 'chartData'));
    }
}
