<?php

namespace App\Livewire\Dashboard;

use App\Models\SaleItem;
use Carbon\Carbon;
use Livewire\Component;

class TopSaleItems extends Component
{
    public function render()
    {
        $topItems = SaleItem::with('product')
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total) as total_amount')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        return view('livewire.dashboard.top-sale-items', [
            'topItems' => $topItems,
        ]);
    }
}
