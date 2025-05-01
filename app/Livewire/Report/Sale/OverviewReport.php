<?php

namespace App\Livewire\Report\Sale;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Livewire\Component;

class OverviewReport extends Component
{
    public $branch_id;

    public $from_date;

    public $to_date;

    public $dataPoints = [];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
    }

    public function updated($key, $value)
    {
        $this->dispatch('updatePieChart', $this->dataPoints);
    }

    public function export() {}

    public function render()
    {
        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;

        $baseQuery = fn ($query) => $query
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to));

        $employees = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->tap($baseQuery)
            ->groupBy('sale_items.employee_id')
            ->select('users.name as employee')
            ->selectRaw('sum(sale_items.total) as total')
            ->selectRaw('sum(sale_items.quantity) as quantity')
            ->orderBy('users.name')
            ->get();

        $products = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->tap($baseQuery)
            ->groupBy('sale_items.product_id')
            ->select('products.name as product', 'products.type')
            ->selectRaw('sum(sale_items.total) as total')
            ->selectRaw('sum(sale_items.quantity) as quantity')
            ->orderBy('total', 'desc')
            ->get();

        $sales = Sale::query()->customerSearch($this->branch_id, $from, $to);
        $saleReturns = SaleReturn::query()->customerSearch($this->branch_id, $from, $to);

        $payments = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->tap($baseQuery)
            ->select('accounts.name as payment_method')
            ->selectRaw('sum(sale_payments.amount) as total')
            ->groupBy('sale_payments.payment_method_id')
            ->pluck('total', 'payment_method');

        $netSales = $sales->sum('gross_amount');
        $saleDiscount = $sales->sum('other_discount');

        $noOfSales = $sales->count();
        $noOfSalesReturns = $saleReturns->count();

        $totalSales = $sales->sum('total');
        $totalSalesReturn = $saleReturns->sum('grand_total');

        foreach ($payments as $title => $amount) {
            $paymentMethods[$title] = $amount;
        }
        $credit = $paymentMethods['Credit'] = $sales->sum('balance');
        $totalPayment = $payments->sum();

        $serviceSale = $products->where('type', 'service')->sum('total');
        $productSale = $products->where('type', 'product')->sum('total');
        $itemTotal = $products->sum('total');

        $this->dataPoints = [];
        foreach ($paymentMethods as $label => $value) {
            $this->dataPoints[] = [
                'label' => $label,
                'y' => $value,
            ];
        }
        $this->dispatch('updatePieChart', $this->dataPoints);

        return view('livewire.report.sale.overview-report', [
            'employees' => $employees,
            'products' => $products,
            'netSales' => $netSales,
            'saleDiscount' => $saleDiscount,
            'serviceSale' => $serviceSale,
            'productSale' => $productSale,
            'itemTotal' => $itemTotal,
            'totalPayment' => $totalPayment,
            'credit' => $credit,
            'paymentMethods' => $paymentMethods,
            'dataPoints' => $this->dataPoints,
            'noOfSales' => $noOfSales,
            'noOfSalesReturns' => $noOfSalesReturns,
            'totalSales' => $totalSales,
            'totalSalesReturn' => $totalSalesReturn,
        ]);
    }
}
