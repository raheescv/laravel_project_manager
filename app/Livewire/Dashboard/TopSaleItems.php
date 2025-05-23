<?php

namespace App\Livewire\Dashboard;

use App\Models\SaleItem;
use Carbon\Carbon;
use Livewire\Component;

class TopSaleItems extends Component
{
    public function render()
    {
        $topItems = SaleItem::with('product:id,name')
            ->whereHas('sale', function ($query) {
                return $query
                    ->where('branch_id', session('branch_id'))
                    ->where('status', 'completed');
            })
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total) as total_amount')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return view('livewire.dashboard.top-sale-items', [
            'topItems' => $topItems,
        ]);
    }
}
