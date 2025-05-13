<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Models\Views\Ledger;
use App\Models\Product;
use App\Models\Purchase;
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
        $lastWeekSale = Sale::currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('grand_total');
        $sale_percentage = $lastWeekSale ? (($weeklySale - $lastWeekSale) / $lastWeekSale) * 100 : 0;

        $weeklyPurchase = Purchase::currentBranch()->last7Days()->sum('grand_total');
        $lastWeekPurchase = Purchase::currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('grand_total');
        $purchase_percentage = $lastWeekPurchase ? (($weeklyPurchase - $lastWeekPurchase) / $lastWeekPurchase) * 100 : 0;

        $weeklyExpense = Ledger::expenseList([])->currentBranch()->last7Days()->sum('debit');
        $lastWeekExpense = Ledger::expenseList([])->currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('debit');
        $expense_percentage = $lastWeekExpense ? (($weeklyExpense - $lastWeekExpense) / $lastWeekExpense) * 100 : 0;

        $weeklyIncome = Ledger::incomeList([])->currentBranch()->last7Days()->sum('credit');
        $lastWeekIncome = Ledger::incomeList([])->currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('credit');
        $income_percentage = $lastWeekIncome ? (($weeklyIncome - $lastWeekIncome) / $lastWeekIncome) * 100 : 0;

        $stockCost = Inventory::currentBranch()->whereHas('product', function ($query) {
            return $query->where('products.type', 'product');
        })->sum('total');
        $category = Category::whereNull('parent_id')->count();
        $product = Product::product()->count();
        $service = Product::service()->count();

        return view('livewire.dashboard.top-card', compact(
            'todaySale', 'todayPayment',
            'weeklySale', 'sale_percentage',
            'weeklyPurchase', 'purchase_percentage',
            'weeklyExpense', 'expense_percentage',
            'weeklyIncome', 'income_percentage',
            'stockCost', 'category', 'product', 'service'
        ));
    }
}
