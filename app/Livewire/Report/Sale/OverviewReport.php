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

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
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

        $overview['Net Sales'] = $sales->sum('gross_amount');
        $overview['No Of Sales'] = $sales->count();
        $overview['No Of Sales Returns'] = $saleReturns->count();
        $overview['Sale Discount'] = $sales->sum('other_discount');
        $overview['Total Sales'] = $sales->sum('total');
        $overview['Total Sales Return'] = $saleReturns->sum('grand_total');

        foreach ($payments as $title => $amount) {
            $overview[$title] = $amount;
        }
        $overview['Credit'] = $sales->sum('balance');
        $overview['Payment Mode Total'] = $payments->sum();

        $overview['Service Sale'] = $products->where('type', 'service')->sum('total');
        $overview['Product Sale'] = $products->where('type', 'product')->sum('total');
        $overview['Item Total'] = $products->sum('total');

        return view('livewire.report.sale.overview-report', [
            'employees' => $employees,
            'products' => $products,
            'overview' => $overview,
        ]);
    }
}
