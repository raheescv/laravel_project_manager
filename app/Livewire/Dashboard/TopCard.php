<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use Livewire\Component;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Inventory;
use App\Models\Product;

class TopCard extends Component
{
    public function render()
    {
        $todaySale = Sale::today()->sum('grand_total');
        $todayPayment = SalePayment::today()->sum('amount');

        $weeklySale = Sale::last7Days()->sum('grand_total');
        $weeklyPayment = SalePayment::last7Days()->sum('amount');
        $stockCost = Inventory::sum('total');
        $category = Category::whereNull('parent_id')->count();
        $product = Product::product()->count();
        $service = Product::service()->count();

        return view('livewire.dashboard.top-card',compact('todaySale','todayPayment','weeklySale','weeklyPayment','stockCost','category','product','service'));
    }
}
