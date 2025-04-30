<?php

namespace App\Livewire\Dashboard\Sale;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Overview extends Component
{
    public function render()
    {
        $todaySale = Sale::completed()->currentBranch()->today()->sum('grand_total');
        $todayPayment = SalePayment::completedSale()->currentBranch()->today()->sum('amount');
        $credit = $todaySale - $todayPayment;
        $highestSale = Sale::completed()->currentBranch()->today()->max('grand_total');
        $lowestSale = Sale::completed()->currentBranch()->today()->min('grand_total');

        $paymentData = SalePayment::completedSale()->currentBranch()->today()
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

        $sales = Sale::completed()->currentBranch()->last30Days()
            ->selectRaw("DATE_FORMAT(date, '%d-%b') as date, SUM(grand_total) as amount")
            ->groupBy('date')
            ->pluck('amount', 'date');

        $dates = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('d-M');
            $dates->put($date, 0);
        }

        $data = $dates->merge($sales)->map(function ($amount, $date) {
            return [
                'date' => $date,
                'amount' => (float) $amount,
            ];
        })->values()->toArray();

        return view('livewire.dashboard.sale.overview', compact('data', 'paymentData', 'highestSale', 'lowestSale', 'todaySale', 'todayPayment'));
    }
}
