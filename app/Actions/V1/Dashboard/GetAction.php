<?php

namespace App\Actions\V1\Dashboard;

use App\Models\Account;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

class GetAction
{
    /**
     * Build the admin overview dashboard: summary counts with display titles.
     */
    public function execute(): array
    {
        $today = today()->toDateString();

        $salesTotal = round((float) Sale::query()->completed()->whereDate('date', $today)->sum('paid'), 2);
        $billsCount = Sale::query()->completed()->whereDate('date', $today)->count();
        $employees = User::query()->employee()->where('is_active', true)->count();
        $customers = Account::query()->customer()->count();
        $products = Product::query()->product()->count();
        $services = Product::query()->service()->count();

        return [
            'date' => $today,
            'cards' => [
                ['title' => "Today's Sales", 'value' => $salesTotal, 'type' => 'currency'],
                ['title' => "Today's Bills", 'value' => $billsCount, 'type' => 'count'],
                ['title' => 'Active Employees', 'value' => $employees, 'type' => 'count'],
                ['title' => 'Customers', 'value' => $customers, 'type' => 'count'],
                ['title' => 'Products', 'value' => $products, 'type' => 'count'],
                ['title' => 'Services', 'value' => $services, 'type' => 'count'],
            ],
        ];
    }
}
