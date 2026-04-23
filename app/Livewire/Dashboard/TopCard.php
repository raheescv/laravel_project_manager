<?php

namespace App\Livewire\Dashboard;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\JournalEntry;
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
        $sale_percentage = $this->calculatePercentageChange($weeklySale, $lastWeekSale);

        $weeklyPurchase = Purchase::currentBranch()->last7Days()->sum('grand_total');
        $lastWeekPurchase = Purchase::currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('grand_total');
        $purchase_percentage = $this->calculatePercentageChange($weeklyPurchase, $lastWeekPurchase);

        $weeklyExpense = JournalEntry::expenseList([])->currentBranch()->last7Days()->sum('debit');
        $lastWeekExpense = JournalEntry::expenseList([])->currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('debit');
        $expense_percentage = $this->calculatePercentageChange($weeklyExpense, $lastWeekExpense);

        $weeklyIncome = JournalEntry::incomeList([])->currentBranch()->last7Days()->sum('credit');
        $lastWeekIncome = JournalEntry::incomeList([])->currentBranch()->whereBetween('date', [now()->subDays(14), now()->subDays(7)])->sum('credit');
        $income_percentage = $this->calculatePercentageChange($weeklyIncome, $lastWeekIncome);

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

    protected function calculatePercentageChange(float|int|string $currentValue, float|int|string $previousValue): float
    {
        $currentValue = (float) $currentValue;
        $previousValue = (float) $previousValue;

        if ($previousValue === 0.0) {
            return 0.0;
        }

        return (($currentValue - $previousValue) / $previousValue) * 100;
    }
}
