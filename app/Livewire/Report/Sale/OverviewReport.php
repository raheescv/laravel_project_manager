<?php

namespace App\Livewire\Report\Sale;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class OverviewReport extends Component
{
    use WithPagination;

    public $branchId;

    public $fromDate;

    public $toDate;

    public $dataPoints = [];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;

    public $productSearch = '';

    public $productSortField = 'net_amount';

    public $productSortDirection = 'desc';

    public $employeeSearch = '';

    public $employeeSortField = 'total';

    public $employeeSortDirection = 'desc';

    public $employeePerPage = 10;

    public $productPerPage = 10;

    public function mount()
    {
        $this->fromDate = date('Y-m-d');
        $this->toDate = date('Y-m-d');
        $this->branchId = session('branch_id');
    }

    public function export()
    {
        // TODO: Implement export functionality
    }

    public function sortBy($field)
    {
        [$type, $field] = explode('.', $field);
        $sortField = "{$type}SortField";
        $sortDirection = "{$type}SortDirection";

        if ($this->{$sortField} === $field) {
            $this->{$sortDirection} = $this->{$sortDirection} === 'asc' ? 'desc' : 'asc';
        } else {
            $this->{$sortField} = $field;
            $this->{$sortDirection} = 'desc';
        }
    }

    public function updatedProductSearch()
    {
        $this->resetPage('products-page');
    }

    public function updatedEmployeeSearch()
    {
        $this->resetPage('employees-page');
    }

    public function updatedEmployeePerPage()
    {
        $this->resetPage('employees-page');
    }

    public function updatedProductPerPage()
    {
        $this->resetPage('products-page');
    }

    public function render()
    {
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->toDateString() : null;
        $to = $this->toDate ? Carbon::parse($this->toDate)->toDateString() : null;

        $baseQuery = $this->getBaseSaleQuery($from, $to);
        $baseReturnQuery = $this->getBaseReturnQuery($from, $to);

        $employees = $this->getEmployeesQuery($baseQuery);
        $products = $this->getProductsQuery($baseQuery, $baseReturnQuery);

        $sales = Sale::query()->customerSearch($this->branchId, $from, $to);
        $saleReturns = SaleReturn::query()->customerSearch($this->branchId, $from, $to);

        $salePayments = $this->getSalePaymentsQuery($baseQuery);
        $saleReturnPayments = $this->getSaleReturnPaymentsQuery($baseReturnQuery);
        $payments = $this->getPaymentsQuery($baseQuery);

        $totals = $this->calculateTotals($sales, $saleReturns, $payments, $baseQuery, $baseReturnQuery);
        $employeeStats = $this->calculateEmployeeStats($baseQuery);
        $productStats = $this->calculateProductStats($baseQuery, $baseReturnQuery);

        $this->prepareChartData($totals['paymentMethods']);

        return view('livewire.report.sale.overview-report',
            array_merge(
                [
                    'employees' => $employees,
                    'products' => $products,
                    'salePayments' => $salePayments,
                    'saleReturnPayments' => $saleReturnPayments,
                ],
                $totals,
                $employeeStats,
                $productStats,
                [
                    'dataPoints' => $this->dataPoints,
                ]
            )
        );
    }

    private function getBaseSaleQuery(?string $from, ?string $to): callable
    {
        return fn ($query) => $query
            ->when($this->branchId, fn ($q) => $q->where('sales.branch_id', $this->branchId))
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->where('sales.status', 'completed');
    }

    private function getBaseReturnQuery(?string $from, ?string $to): callable
    {
        return fn ($query) => $query
            ->when($this->branchId, fn ($q) => $q->where('sale_returns.branch_id', $this->branchId))
            ->when($from, fn ($q) => $q->where('sale_returns.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_returns.date', '<=', $to))
            ->where('sale_returns.status', 'completed');
    }

    private function getEmployeesQuery(callable $baseQuery)
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->tap($baseQuery)
            ->when($this->employeeSearch, function ($query) {
                $query->where('users.name', 'like', '%'.$this->employeeSearch.'%');
            })
            ->groupBy('sale_items.employee_id')
            ->select('users.id', 'users.name as employee')
            ->selectRaw('SUM(sale_items.total) as total')
            ->selectRaw('SUM(sale_items.quantity) as quantity')
            ->orderBy($this->employeeSortField, $this->employeeSortDirection)
            ->paginate($this->employeePerPage, ['*'], 'employees-page');
    }

    private function getProductsQuery(callable $baseQuery, callable $baseReturnQuery)
    {
        $saleItemsQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->tap($baseQuery)
            ->when($this->productSearch, function ($query) {
                $query->where('products.name', 'like', '%'.$this->productSearch.'%');
            })
            ->select(
                'products.id',
                'products.name as product',
                'products.type',
                DB::raw('SUM(sale_items.quantity) as sales_quantity'),
                DB::raw('0 as return_quantity'),
                DB::raw('SUM(sale_items.total) as sale_total'),
                DB::raw('0 as sale_return_total')
            )
            ->groupBy('products.id', 'products.name', 'products.type');

        $saleReturnItemsQuery = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->tap($baseReturnQuery)
            ->when($this->productSearch, function ($query) {
                $query->where('products.name', 'like', '%'.$this->productSearch.'%');
            })
            ->select(
                'products.id',
                'products.name as product',
                'products.type',
                DB::raw('0 as sales_quantity'),
                DB::raw('SUM(sale_return_items.quantity) as return_quantity'),
                DB::raw('0 as sale_total'),
                DB::raw('SUM(sale_return_items.total) as sale_return_total')
            )
            ->groupBy('products.id', 'products.name', 'products.type');

        $unionQuery = $saleItemsQuery->unionAll($saleReturnItemsQuery);

        return DB::query()
            ->fromSub($unionQuery, 'unified_items')
            ->select(
                'id',
                'product',
                'type',
                DB::raw('SUM(sales_quantity) as sales_quantity'),
                DB::raw('SUM(return_quantity) as return_quantity'),
                DB::raw('SUM(sale_total) as sale_total'),
                DB::raw('SUM(sale_return_total) as sale_return_total'),
                DB::raw('SUM(sale_total) - SUM(sale_return_total) as net_amount')
            )
            ->groupBy('id', 'product', 'type')
            ->orderBy($this->productSortField, $this->productSortDirection)
            ->paginate($this->productPerPage, ['*'], 'products-page');
    }

    private function getSalePaymentsQuery(callable $baseQuery)
    {
        return SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->tap($baseQuery)
            ->select(
                'accounts.name as payment_method',
                'branches.name as branch_name'
            )
            ->selectRaw("'sale' as payment_type")
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->groupBy('sale_payments.payment_method_id', 'branches.name')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getSaleReturnPaymentsQuery(callable $baseReturnQuery)
    {
        return SaleReturnPayment::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_payments.sale_return_id')
            ->join('accounts', 'accounts.id', '=', 'sale_return_payments.payment_method_id')
            ->join('branches', 'branches.id', '=', 'sale_returns.branch_id')
            ->tap($baseReturnQuery)
            ->select(
                'accounts.name as payment_method',
                'branches.name as branch_name'
            )
            ->selectRaw('SUM(sale_return_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_return_payments.sale_return_id) as transaction_count')
            ->groupBy('sale_return_payments.payment_method_id', 'branches.name')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getPaymentsQuery(callable $baseQuery)
    {
        return SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->tap($baseQuery)
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->groupBy('sale_payments.payment_method_id')
            ->pluck('total', 'payment_method');
    }

    private function calculateTotals($sales, $saleReturns, $payments, callable $baseQuery, callable $baseReturnQuery): array
    {
        $netSales = $sales->sum('gross_amount');
        $saleDiscount = $sales->sum('other_discount');
        $noOfSales = $sales->count();
        $noOfSalesReturns = $saleReturns->count();
        $totalSales = $sales->sum('total');
        $totalSalesReturn = $saleReturns->sum('grand_total');
        $totalPayment = $payments->sum();

        $paymentMethods = [];
        $paymentMethods['Credit'] = $sales->sum('balance');
        foreach ($payments as $title => $amount) {
            $paymentMethods[$title] = $amount;
        }

        return [
            'netSales' => $netSales,
            'saleDiscount' => $saleDiscount,
            'noOfSales' => $noOfSales,
            'noOfSalesReturns' => $noOfSalesReturns,
            'totalSales' => $totalSales,
            'totalSalesReturn' => $totalSalesReturn,
            'totalPayment' => $totalPayment,
            'credit' => $paymentMethods['Credit'],
            'paymentMethods' => $paymentMethods,
        ];
    }

    private function calculateEmployeeStats(callable $baseQuery): array
    {
        $totalEmployees = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->tap($baseQuery)
            ->when($this->employeeSearch, function ($query) {
                $query->where('users.name', 'like', '%'.$this->employeeSearch.'%');
            });

        return [
            'employeeQuantity' => $totalEmployees->sum('sale_items.quantity'),
            'employeeTotal' => $totalEmployees->sum('sale_items.total'),
        ];
    }

    private function calculateProductStats(callable $baseQuery, callable $baseReturnQuery): array
    {
        $totalProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->tap($baseQuery)
            ->when($this->productSearch, function ($query) {
                $query->where('products.name', 'like', '%'.$this->productSearch.'%');
            });

        $totalReturnedProducts = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->tap($baseReturnQuery)
            ->when($this->productSearch, function ($query) {
                $query->where('products.name', 'like', '%'.$this->productSearch.'%');
            });

        $totalProductQuantity = $totalProducts->sum('sale_items.quantity');
        $totalReturnedQuantity = $totalReturnedProducts->sum('sale_return_items.quantity');
        $itemTotal = $totalProducts->sum('sale_items.total');
        $totalSaleReturnAmount = $totalReturnedProducts->sum('sale_return_items.total');
        $netItemTotal = $itemTotal - $totalSaleReturnAmount;

        $productSale = (clone $totalProducts)
            ->where('products.type', 'product')
            ->sum('sale_items.total');

        $serviceSale = (clone $totalProducts)
            ->where('products.type', 'service')
            ->sum('sale_items.total');

        return [
            'totalProductQuantity' => $totalProductQuantity,
            'totalReturnedQuantity' => $totalReturnedQuantity,
            'itemTotal' => $itemTotal,
            'totalSaleReturnAmount' => $totalSaleReturnAmount,
            'netItemTotal' => $netItemTotal,
            'productSale' => $productSale,
            'serviceSale' => $serviceSale,
        ];
    }

    private function prepareChartData(array $paymentMethods): void
    {
        $this->dataPoints = [];
        foreach ($paymentMethods as $label => $value) {
            $this->dataPoints[] = [
                'label' => $label,
                'y' => $value,
            ];
        }
        $this->dispatch('updatePieChart', $this->dataPoints);
    }
}
