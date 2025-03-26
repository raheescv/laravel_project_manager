<?php

namespace App\Livewire\Dashboard\Sale;

use App\Models\Sale;
use App\Models\SalePayment;
use Livewire\Component;

class Overview extends Component
{
    public function render()
    {
        $todaySale = Sale::today()->sum('grand_total');
        $todayPayment = SalePayment::today()->sum('amount');
        $credit = $todaySale - $todayPayment;
        $highestSale = Sale::today()->max('grand_total');
        $lowestSale = Sale::today()->min('grand_total');

        $paymentData = SalePayment::today()
            ->join('accounts', 'payment_method_id', '=', 'accounts.id')->select('accounts.name as method')
            ->selectRaw('sum(amount) as amount')
            ->groupBy('payment_method_id')
            ->get()
            ->toArray();

        if ($credit) {
            $paymentData[] = [
                'method' => 'Credit',
                'amount' => $credit,
            ];
        }

        $totalAmount = $todayPayment + $credit;
        foreach ($paymentData as $key => $item) {
            $paymentData[$key]['percentage'] = $totalAmount ? round(($item['amount'] / $totalAmount) * 100, 2) : 0;
        }

        $data = Sale::last30Days()
            ->selectRaw("DATE_FORMAT(date, '%d-%b') as date, SUM(grand_total) as amount")
            ->groupBy('date')
            ->get()
            ->toArray();

        return view('livewire.dashboard.sale.overview', compact('data', 'paymentData', 'highestSale', 'lowestSale', 'todaySale', 'todayPayment'));
    }
}
