<?php

namespace App\Livewire\Dashboard;

use App\Models\SaleItem;
use App\Models\TailoringOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TopSaleItems extends Component
{
    public function render()
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.branch_id', session('branch_id'))
            ->where('sales.status', 'completed')
            ->whereDate('sale_items.created_at', Carbon::today())
            ->selectRaw('products.name as item_name, SUM(sale_items.quantity) as total_quantity, SUM(sale_items.total) as total_amount')
            ->groupBy('products.name')
            ->get();

        $tailoringItems = TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_order_items.tailoring_order_id', '=', 'tailoring_orders.id')
            ->when(session('branch_id'), fn ($q) => $q->where('tailoring_orders.branch_id', session('branch_id')))
            ->whereDate('tailoring_order_items.created_at', Carbon::today())
            ->selectRaw('COALESCE(NULLIF(tailoring_order_items.product_name, ""), "Tailoring Item") as item_name')
            ->selectRaw('SUM(tailoring_order_items.quantity) as total_quantity')
            ->selectRaw('SUM(COALESCE(tailoring_order_items.total, 0)) as total_amount')
            ->groupBy(DB::raw('COALESCE(NULLIF(tailoring_order_items.product_name, ""), "Tailoring Item")'))
            ->get();

        $topItems = $saleItems
            ->concat($tailoringItems)
            ->groupBy('item_name')
            ->map(function ($rows, $itemName) {
                return (object) [
                    'item_name' => $itemName,
                    'total_quantity' => $rows->sum('total_quantity'),
                    'total_amount' => $rows->sum('total_amount'),
                ];
            })
            ->sortByDesc('total_quantity')
            ->take(10)
            ->values();

        return view('livewire.dashboard.top-sale-items', [
            'topItems' => $topItems,
        ]);
    }
}
