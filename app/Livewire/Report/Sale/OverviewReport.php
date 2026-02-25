<?php

namespace App\Livewire\Report\Sale;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnPayment;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderItemTailor;
use App\Models\TailoringPayment;
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

    private function getEmployeesQuery(callable $baseQuery, callable $baseReturnQuery)
    {
        $saleItemsQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->tap($baseQuery)
            ->when($this->employeeSearch, function ($query) {
                $query->where('users.name', 'like', '%'.trim($this->employeeSearch).'%');
            })
            ->select('users.id', 'users.name as employee')
            ->selectRaw('SUM(sale_items.total) as sale_total')
            ->selectRaw('0 as return_total')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sale_quantity')
            ->selectRaw('0 as return_quantity')
            ->groupBy('sale_items.employee_id', 'users.id', 'users.name');

        $saleReturnItemsQuery = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('users', 'users.id', '=', 'sale_return_items.employee_id')
            ->tap($baseReturnQuery)
            ->when($this->employeeSearch, function ($query) {
                $query->where('users.name', 'like', '%'.trim($this->employeeSearch).'%');
            })
            ->select('users.id', 'users.name as employee')
            ->selectRaw('0 as sale_total')
            ->selectRaw('SUM(sale_return_items.total) as return_total')
            ->selectRaw('0 as sale_quantity')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_quantity')
            ->groupBy('sale_return_items.employee_id', 'users.id', 'users.name');

        $unionQuery = $saleItemsQuery->unionAll($saleReturnItemsQuery);

        return DB::query()
            ->fromSub($unionQuery, 'unified_employees')
            ->select(
                'id',
                'employee',
                DB::raw('SUM(sale_total) - SUM(return_total) as total'),
                DB::raw('SUM(sale_quantity) - SUM(return_quantity) as quantity')
            )
            ->groupBy('id', 'employee')
            ->havingRaw('SUM(sale_quantity) - SUM(return_quantity) > 0')
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
                $query->where('products.name', 'like', '%'.trim($this->productSearch).'%');
            })
            ->select(
                'products.id',
                'products.name as product',
                'products.type',
                DB::raw('SUM(sale_items.base_unit_quantity) as sales_quantity'),
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
                $query->where('products.name', 'like', '%'.trim($this->productSearch).'%');
            })
            ->select(
                'products.id',
                'products.name as product',
                'products.type',
                DB::raw('0 as sales_quantity'),
                DB::raw('SUM(sale_return_items.base_unit_quantity) as return_quantity'),
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

    private function getSalePaymentsQuery()
    {
        $salePayments = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->when($this->branchId, fn ($q) => $q->where('sales.branch_id', $this->branchId))
            ->when($this->fromDate, fn ($q) => $q->where('sale_payments.date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->where('sale_payments.date', '<=', $this->toDate))
            ->where('sales.status', 'completed')
            ->select('accounts.name as payment_method')
            ->selectRaw("'sale' as payment_type")
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_payments.sale_id) as transaction_count')
            ->groupBy('sale_payments.payment_method_id')
            ->orderBy('total', 'desc')
            ->get();

        $tailoringPayments = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_payments.tailoring_order_id')
            ->join('accounts', 'accounts.id', '=', 'tailoring_payments.payment_method_id')
            ->when($this->branchId, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branchId))
            ->when($this->fromDate, fn ($q) => $q->where('tailoring_payments.date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->where('tailoring_payments.date', '<=', $this->toDate))
            ->select('accounts.name as payment_method')
            ->selectRaw("'tailoring' as payment_type")
            ->selectRaw('SUM(tailoring_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT tailoring_payments.tailoring_order_id) as transaction_count')
            ->groupBy('tailoring_payments.payment_method_id')
            ->orderBy('total', 'desc')
            ->get();

        return $salePayments
            ->concat($tailoringPayments)
            ->groupBy('payment_method')
            ->map(function ($rows, $paymentMethod) {
                $paymentType = $rows->pluck('payment_type')->unique();

                return (object) [
                    'payment_method' => $paymentMethod,
                    'payment_type' => $paymentType->count() === 1 ? $paymentType->first() : 'mixed',
                    'total' => (float) $rows->sum('total'),
                    'transaction_count' => (int) $rows->sum('transaction_count'),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function getSaleReturnPaymentsQuery()
    {
        return SaleReturnPayment::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_payments.sale_return_id')
            ->join('accounts', 'accounts.id', '=', 'sale_return_payments.payment_method_id')
            ->when($this->branchId, fn ($q) => $q->where('sale_returns.branch_id', $this->branchId))
            ->when($this->fromDate, fn ($q) => $q->where('sale_return_payments.date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->where('sale_return_payments.date', '<=', $this->toDate))
            ->where('sale_returns.status', 'completed')
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(sale_return_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_return_payments.sale_return_id) as transaction_count')
            ->groupBy('sale_return_payments.payment_method_id')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getPaymentsQuery($salePayments)
    {
        return $salePayments->pluck('total', 'payment_method');
    }

    private function calculateTotals($sales, $tailoringOrders, $saleReturns, $payments): array
    {
        $salesMetrics = $this->calculateSalesMetrics($sales, $tailoringOrders);
        $saleReturnMetrics = $this->calculateSaleReturnMetrics($saleReturns);
        $paymentMethods = $this->calculatePaymentMethods($sales, $tailoringOrders, $payments);

        return array_merge(
            $salesMetrics,
            $saleReturnMetrics,
            [
                'totalPayment' => $payments->sum(),
                'credit' => $paymentMethods['Credit'] ?? 0,
                'paymentMethods' => $paymentMethods,
            ]
        );
    }

    private function calculateSalesMetrics($sales, $tailoringOrders): array
    {
        $saleMetrics = (clone $sales)
            ->selectRaw('
                COALESCE(SUM(gross_amount), 0) as gross_sales,
                COALESCE(SUM(grand_total), 0) as net_sales,
                COALESCE(SUM(total), 0) as total_sales,
                COALESCE(SUM(balance), 0) as sale_balance,
                COALESCE(SUM(other_discount), 0) + COALESCE(SUM(item_discount), 0) - COALESCE(SUM(round_off), 0) as sale_discount,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as sale_balance_count,
                COUNT(*) as no_of_sales
            ')
            ->first();

        $tailoringMetrics = (clone $tailoringOrders)
            ->selectRaw('
                COALESCE(SUM(gross_amount), 0) as gross_sales,
                COALESCE(SUM(grand_total), 0) as net_sales,
                COALESCE(SUM(total), 0) as total_sales,
                COALESCE(SUM(balance), 0) as sale_balance,
                COALESCE(SUM(other_discount), 0) + COALESCE(SUM(item_discount), 0) - COALESCE(SUM(round_off), 0)  as sale_discount,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as sale_balance_count,
                COUNT(*) as no_of_sales
            ')
            ->first();

        return [
            'grossSales' => (float) $saleMetrics->gross_sales + (float) $tailoringMetrics->gross_sales,
            'netSales' => (float) $saleMetrics->net_sales + (float) $tailoringMetrics->net_sales,
            'totalSales' => (float) $saleMetrics->total_sales + (float) $tailoringMetrics->total_sales,
            'saleBalance' => (float) $saleMetrics->sale_balance + (float) $tailoringMetrics->sale_balance,
            'saleDiscount' => (float) $saleMetrics->sale_discount + (float) $tailoringMetrics->sale_discount,
            'saleBalanceCount' => (int) $saleMetrics->sale_balance_count + (int) $tailoringMetrics->sale_balance_count,
            'noOfSales' => (int) $saleMetrics->no_of_sales + (int) $tailoringMetrics->no_of_sales,
        ];
    }

    private function calculateSaleReturnMetrics($saleReturns): array
    {
        $metrics = (clone $saleReturns)
            ->selectRaw('
                COALESCE(SUM(grand_total), 0) as total_sales_return,
                COALESCE(SUM(balance), 0) as sale_return_balance,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as sale_return_balance_count,
                COUNT(*) as no_of_sales_returns
            ')
            ->first();

        return [
            'totalSalesReturn' => (float) $metrics->total_sales_return,
            'saleReturnBalance' => (float) $metrics->sale_return_balance,
            'saleReturnBalanceCount' => (int) $metrics->sale_return_balance_count,
            'noOfSalesReturns' => (int) $metrics->no_of_sales_returns,
        ];
    }

    private function calculatePaymentMethods($sales, $tailoringOrders, $payments): array
    {
        $paymentMethods = [
            'Credit' => ($sales->sum('balance') ?? 0) + ($tailoringOrders->sum('balance') ?? 0),
        ];

        foreach ($payments as $title => $amount) {
            $paymentMethods[$title] = $amount;
        }

        return $paymentMethods;
    }

    private function calculateEmployeeStats(callable $baseQuery, callable $baseReturnQuery): array
    {
        $totalSaleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->tap($baseQuery)
            ->when($this->employeeSearch, function ($query) {
                $query->where('users.name', 'like', '%'.trim($this->employeeSearch).'%');
            });

        $totalSaleReturnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('users', 'users.id', '=', 'sale_return_items.employee_id')
            ->tap($baseReturnQuery)
            ->where('sale_return_items.base_unit_quantity', '>', 0)
            ->when($this->employeeSearch, function ($query) {
                $query->where('users.name', 'like', '%'.trim($this->employeeSearch).'%');
            });

        $saleQuantity = $totalSaleItems->sum('sale_items.base_unit_quantity');
        $returnQuantity = $totalSaleReturnItems->sum('sale_return_items.base_unit_quantity');
        $saleTotal = $totalSaleItems->sum('sale_items.total');
        $returnTotal = $totalSaleReturnItems->sum('sale_return_items.total');

        return [
            'employeeQuantity' => $saleQuantity - $returnQuantity,
            'employeeTotal' => $saleTotal - $returnTotal,
        ];
    }

    private function calculateProductStats(callable $baseQuery, callable $baseReturnQuery): array
    {
        $totalProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->tap($baseQuery)
            ->when($this->productSearch, function ($query) {
                $query->where('products.name', 'like', '%'.trim($this->productSearch).'%');
            });

        $totalReturnedProducts = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->tap($baseReturnQuery)
            ->when($this->productSearch, function ($query) {
                $query->where('products.name', 'like', '%'.trim($this->productSearch).'%');
            });

        $totalProductQuantity = $totalProducts->sum('sale_items.base_unit_quantity');
        $totalReturnedQuantity = $totalReturnedProducts->sum('sale_return_items.base_unit_quantity');
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

    private function getTailorPerformance(?string $from, ?string $to)
    {
        return TailoringOrderItemTailor::query()
            ->join('tailoring_order_items', 'tailoring_order_items.id', '=', 'tailoring_order_item_tailors.tailoring_order_item_id')
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->join('users', 'users.id', '=', 'tailoring_order_item_tailors.tailor_id')
            ->when($this->branchId, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branchId))
            ->when($from, fn ($q) => $q->where('tailoring_orders.order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_orders.order_date', '<=', $to))
            ->select('users.id', 'users.name as tailor')
            ->selectRaw("SUM(CASE WHEN tailoring_order_item_tailors.status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN tailoring_order_item_tailors.status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN tailoring_order_item_tailors.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count")
            ->selectRaw('AVG(tailoring_order_item_tailors.rating) as avg_rating')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('completed_count')
            ->orderByDesc('delivered_count')
            ->get();
    }

    private function getTailoringItemPerformance(?string $from, ?string $to)
    {
        return TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->when($this->branchId, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branchId))
            ->when($from, fn ($q) => $q->where('tailoring_orders.order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_orders.order_date', '<=', $to))
            ->select('tailoring_order_items.product_name')
            ->selectRaw('COALESCE(SUM(tailoring_order_items.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(tailoring_order_items.total), 0) as total_amount')
            ->groupBy('tailoring_order_items.product_name')
            ->orderByDesc('total_amount')
            ->get();
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

    public function render()
    {
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->toDateString() : null;
        $to = $this->toDate ? Carbon::parse($this->toDate)->toDateString() : null;

        $baseQuery = $this->getBaseSaleQuery($from, $to);
        $baseReturnQuery = $this->getBaseReturnQuery($from, $to);

        $employees = $this->getEmployeesQuery($baseQuery, $baseReturnQuery);
        $products = $this->getProductsQuery($baseQuery, $baseReturnQuery);

        $sales = Sale::query()->customerSearch($this->branchId, $from, $to);
        $tailoringOrders = TailoringOrder::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($from, fn ($q) => $q->where('order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('order_date', '<=', $to));
        $saleReturns = SaleReturn::query()->customerSearch($this->branchId, $from, $to);
        $tailorPerformance = $this->getTailorPerformance($from, $to);
        $tailoringItemPerformance = $this->getTailoringItemPerformance($from, $to);

        $salePayments = $this->getSalePaymentsQuery();
        $saleReturnPayments = $this->getSaleReturnPaymentsQuery();
        $payments = $this->getPaymentsQuery($salePayments);

        $totals = $this->calculateTotals($sales, $tailoringOrders, $saleReturns, $payments);
        $employeeStats = $this->calculateEmployeeStats($baseQuery, $baseReturnQuery);
        $productStats = $this->calculateProductStats($baseQuery, $baseReturnQuery);

        $this->prepareChartData($totals['paymentMethods']);

        return view('livewire.report.sale.overview-report',
            array_merge(
                [
                    'employees' => $employees,
                    'products' => $products,
                    'salePayments' => $salePayments,
                    'saleReturnPayments' => $saleReturnPayments,
                    'tailorPerformance' => $tailorPerformance,
                    'tailoringItemPerformance' => $tailoringItemPerformance,
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
}
