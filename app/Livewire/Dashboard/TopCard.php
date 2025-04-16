<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use Livewire\Component;

class TopCard extends Component
{
    public function render()
    {
        $todaySale = Sale::currentBranch()->today()->sum('grand_total');
        $todayPayment = SalePayment::today()->sum('amount');

        $weeklySale = Sale::currentBranch()->last7Days()->sum('grand_total');
        $weeklyPayment = SalePayment::currentBranch()->last7Days()->sum('amount');
        $stockCost = Inventory::currentBranch()->whereHas('product', function ($query) {
            return $query->where('products.type', 'product');
        })->sum('total');
        $category = Category::whereNull('parent_id')->count();
        $product = Product::product()->count();
        $service = Product::service()->count();

        return view('livewire.dashboard.top-card', compact('todaySale', 'todayPayment', 'weeklySale', 'weeklyPayment', 'stockCost', 'category', 'product', 'service'));
    }
}
