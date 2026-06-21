<?php

namespace App\Actions\V1\Report;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnPayment;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\TailoringPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales Overview report for the mobile app.
 *
 * Returns the same aggregates the web Livewire `OverviewReport` computes
 * (sales performance + payment overview + per-employee / per-product
 * breakdowns) as a flat JSON payload. Tailoring orders are folded into the
 * money totals exactly as the web report does, so the numbers agree.
 */
class OverviewAction
{
    /** How many rows the breakdown lists return (mobile shows a ranked list, not pages). */
    private const TOP_LIMIT = 10;

    public function execute(?string $from, ?string $to, ?int $branchId): array
    {
        $sales = Sale::query()->customerSearch($branchId, $from, $to);
        $saleReturns = SaleReturn::query()->customerSearch($branchId, $from, $to);
        $tailoringOrders = TailoringOrder::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('order_date', '<=', $to));

        $salesMetrics = $this->salesMetrics($sales, $tailoringOrders);
        $returnMetrics = $this->returnMetrics($saleReturns);
        $productStats = $this->productStats($branchId, $from, $to);

        $salePayments = $this->salePayments($branchId, $from, $to);
        $returnPayments = $this->returnPayments($branchId, $from, $to);

        $totalPayment = (float) $salePayments->sum('total');
        $returnsPaymentTotal = (float) $returnPayments->sum('total');
        $salesTxns = (int) $salePayments->sum('transaction_count');
        $returnsTxns = (int) $returnPayments->sum('transaction_count');

        $totalSales = $salesMetrics['totalSales'];
        $noOfSales = $salesMetrics['noOfSales'];
        $noOfReturns = $returnMetrics['noOfSalesReturns'];

        $successRate = $noOfSales > 0 ? (($noOfSales - $noOfReturns) / $noOfSales) * 100 : 0;
        $collectionRate = $totalSales > 0 ? ($totalPayment / $totalSales) * 100 : 0;

        // Donut: Credit balance + each method's sale-payment total (mirrors web prepareChartData).
        $chart = [['label' => 'Credit', 'value' => round($salesMetrics['saleBalance'], 2)]];
        foreach ($salePayments as $p) {
            $chart[] = ['label' => $p->payment_method, 'value' => round((float) $p->total, 2)];
        }

        return [
            'period' => ['start_date' => $from, 'end_date' => $to],
            'summary' => [
                'gross_sales' => round($salesMetrics['grossSales'], 2),
                'discount' => round($salesMetrics['saleDiscount'], 2),
                'net_sales' => round($salesMetrics['netSales'], 2),
                'total_sales' => round($totalSales, 2),
                'total_item' => round($productStats['itemTotal'], 2),
                'product_sale' => round($productStats['productSale'], 2),
                'service_sale' => round($productStats['serviceSale'], 2),
                'no_of_sales' => $noOfSales,
                'no_of_sales_returns' => $noOfReturns,
                'success_rate' => round($successRate, 1),
                'collection_rate' => round($collectionRate, 1),
            ],
            'payments' => [
                'sales_total' => round($totalPayment, 2),
                'sales_transactions' => $salesTxns,
                'returns_total' => round($returnsPaymentTotal, 2),
                'returns_transactions' => $returnsTxns,
                'net_payment' => round($totalPayment - $returnsPaymentTotal, 2),
                'total_transactions' => $salesTxns + $returnsTxns,
                'credit' => round($salesMetrics['saleBalance'], 2),
                'methods' => $this->mergeMethods($salePayments, $returnPayments, $salesMetrics, $returnMetrics),
                'chart' => $chart,
            ],
            'employees' => $this->employees($branchId, $from, $to),
            'products' => $this->products($branchId, $from, $to),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function salesMetrics($sales, $tailoringOrders): array
    {
        $expr = '
            COALESCE(SUM(gross_amount), 0) as gross_sales,
            COALESCE(SUM(grand_total), 0) as net_sales,
            COALESCE(SUM(total), 0) as total_sales,
            COALESCE(SUM(balance), 0) as sale_balance,
            COALESCE(SUM(other_discount), 0) + COALESCE(SUM(item_discount), 0) - COALESCE(SUM(round_off), 0) as sale_discount,
            COUNT(CASE WHEN balance > 0 THEN 1 END) as sale_balance_count,
            COUNT(*) as no_of_sales
        ';
        $s = (clone $sales)->selectRaw($expr)->first();
        $t = (clone $tailoringOrders)->selectRaw($expr)->first();

        return [
            'grossSales' => (float) $s->gross_sales + (float) $t->gross_sales,
            'netSales' => (float) $s->net_sales + (float) $t->net_sales,
            'totalSales' => (float) $s->total_sales + (float) $t->total_sales,
            'saleBalance' => (float) $s->sale_balance + (float) $t->sale_balance,
            'saleDiscount' => (float) $s->sale_discount + (float) $t->sale_discount,
            'saleBalanceCount' => (int) $s->sale_balance_count + (int) $t->sale_balance_count,
            'noOfSales' => (int) $s->no_of_sales + (int) $t->no_of_sales,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function returnMetrics($saleReturns): array
    {
        $m = (clone $saleReturns)
            ->selectRaw('
                COALESCE(SUM(grand_total), 0) as total_sales_return,
                COALESCE(SUM(balance), 0) as sale_return_balance,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as sale_return_balance_count,
                COUNT(*) as no_of_sales_returns
            ')
            ->first();

        return [
            'totalSalesReturn' => (float) $m->total_sales_return,
            'saleReturnBalance' => (float) $m->sale_return_balance,
            'saleReturnBalanceCount' => (int) $m->sale_return_balance_count,
            'noOfSalesReturns' => (int) $m->no_of_sales_returns,
        ];
    }

    /**
     * Item-total / product / service split, including tailoring (matches the web report).
     *
     * @return array<string, float>
     */
    private function productStats(?int $branchId, ?string $from, ?string $to): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to));

        $tailoringItems = TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->leftJoin('products', 'products.id', '=', 'tailoring_order_items.product_id')
            ->when($branchId, fn ($q) => $q->where('tailoring_orders.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('tailoring_orders.order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_orders.order_date', '<=', $to));

        $itemTotal = (float) (clone $saleItems)->sum('sale_items.total')
            + (float) (clone $tailoringItems)->sum('tailoring_order_items.total');

        $productSale = (float) (clone $saleItems)->where('products.type', 'product')->sum('sale_items.total')
            + (float) (clone $tailoringItems)->where('products.type', 'product')->sum('tailoring_order_items.total');

        $serviceSale = (float) (clone $saleItems)->where('products.type', 'service')->sum('sale_items.total')
            + (float) (clone $tailoringItems)->where('products.type', 'service')->sum('tailoring_order_items.total');

        return [
            'itemTotal' => $itemTotal,
            'productSale' => $productSale,
            'serviceSale' => $serviceSale,
        ];
    }

    /**
     * Per-method collected amounts (sale + tailoring payments), keyed by account name.
     */
    private function salePayments(?int $branchId, ?string $from, ?string $to): Collection
    {
        $sale = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sale_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_payments.date', '<=', $to))
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_payments.sale_id) as transaction_count')
            ->groupBy('sale_payments.payment_method_id', 'accounts.name')
            ->get();

        $tailoring = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_payments.tailoring_order_id')
            ->join('accounts', 'accounts.id', '=', 'tailoring_payments.payment_method_id')
            ->when($branchId, fn ($q) => $q->where('tailoring_orders.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('tailoring_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_payments.date', '<=', $to))
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(tailoring_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT tailoring_payments.tailoring_order_id) as transaction_count')
            ->groupBy('tailoring_payments.payment_method_id', 'accounts.name')
            ->get();

        return $sale->concat($tailoring)
            ->groupBy('payment_method')
            ->map(fn ($rows, $method) => (object) [
                'payment_method' => $method,
                'total' => (float) $rows->sum('total'),
                'transaction_count' => (int) $rows->sum('transaction_count'),
            ])
            ->sortByDesc('total')
            ->values();
    }

    private function returnPayments(?int $branchId, ?string $from, ?string $to): Collection
    {
        return SaleReturnPayment::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_payments.sale_return_id')
            ->join('accounts', 'accounts.id', '=', 'sale_return_payments.payment_method_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sale_returns.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sale_return_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_return_payments.date', '<=', $to))
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(sale_return_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_return_payments.sale_return_id) as transaction_count')
            ->groupBy('sale_return_payments.payment_method_id', 'accounts.name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Per-method Sales / Returns / Net cards (Card, Cash, …) plus a synthetic Credit row,
     * mirroring the web blade's `$allPaymentMethods` loop.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mergeMethods(Collection $salePayments, Collection $returnPayments, array $salesMetrics, array $returnMetrics): array
    {
        $names = $salePayments->pluck('payment_method')
            ->merge($returnPayments->pluck('payment_method'))
            ->unique()
            ->values();

        $methods = $names->map(function ($name) use ($salePayments, $returnPayments) {
            $sale = $salePayments->firstWhere('payment_method', $name);
            $ret = $returnPayments->firstWhere('payment_method', $name);
            $sales = (float) ($sale->total ?? 0);
            $returns = (float) ($ret->total ?? 0);

            return [
                'method' => $name,
                'sales' => round($sales, 2),
                'returns' => round($returns, 2),
                'net' => round($sales - $returns, 2),
                'sales_transactions' => (int) ($sale->transaction_count ?? 0),
                'returns_transactions' => (int) ($ret->transaction_count ?? 0),
            ];
        })->values()->all();

        // Credit = unpaid balance (mirrors the web "Credit" pseudo-method).
        $methods[] = [
            'method' => 'Credit',
            'sales' => round($salesMetrics['saleBalance'], 2),
            'returns' => round($returnMetrics['saleReturnBalance'], 2),
            'net' => round($salesMetrics['saleBalance'] - $returnMetrics['saleReturnBalance'], 2),
            'sales_transactions' => $salesMetrics['saleBalanceCount'],
            'returns_transactions' => $returnMetrics['saleReturnBalanceCount'],
        ];

        return $methods;
    }

    /**
     * Top stylists by net revenue (sale items − return items), mirrors getEmployeesQuery.
     *
     * @return array<int, array<string, mixed>>
     */
    private function employees(?int $branchId, ?string $from, ?string $to): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->select('users.id', 'users.name as employee')
            ->selectRaw('SUM(sale_items.total) as sale_total')
            ->selectRaw('0 as return_total')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sale_quantity')
            ->selectRaw('0 as return_quantity')
            ->groupBy('sale_items.employee_id', 'users.id', 'users.name');

        $returnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('users', 'users.id', '=', 'sale_return_items.employee_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sale_returns.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sale_returns.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_returns.date', '<=', $to))
            ->select('users.id', 'users.name as employee')
            ->selectRaw('0 as sale_total')
            ->selectRaw('SUM(sale_return_items.total) as return_total')
            ->selectRaw('0 as sale_quantity')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_quantity')
            ->groupBy('sale_return_items.employee_id', 'users.id', 'users.name');

        return DB::query()
            ->fromSub($saleItems->unionAll($returnItems), 'u')
            ->select('id', 'employee')
            ->selectRaw('SUM(sale_total) - SUM(return_total) as total')
            ->selectRaw('SUM(sale_quantity) - SUM(return_quantity) as quantity')
            ->groupBy('id', 'employee')
            ->havingRaw('SUM(sale_quantity) - SUM(return_quantity) > 0')
            ->orderByDesc('total')
            ->limit(self::TOP_LIMIT)
            ->get()
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'name' => $r->employee,
                'quantity' => round((float) $r->quantity, 2),
                'total' => round((float) $r->total, 2),
            ])
            ->all();
    }

    /**
     * Top products/services by net amount (sale − return), mirrors getProductsQuery.
     *
     * @return array<int, array<string, mixed>>
     */
    private function products(?int $branchId, ?string $from, ?string $to): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->select('products.id', 'products.name as product', 'products.type')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sales_quantity')
            ->selectRaw('0 as return_quantity')
            ->selectRaw('SUM(sale_items.total) as sale_total')
            ->selectRaw('0 as sale_return_total')
            ->groupBy('products.id', 'products.name', 'products.type');

        $returnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sale_returns.branch_id', $branchId))
            ->when($from, fn ($q) => $q->where('sale_returns.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_returns.date', '<=', $to))
            ->select('products.id', 'products.name as product', 'products.type')
            ->selectRaw('0 as sales_quantity')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_quantity')
            ->selectRaw('0 as sale_total')
            ->selectRaw('SUM(sale_return_items.total) as sale_return_total')
            ->groupBy('products.id', 'products.name', 'products.type');

        return DB::query()
            ->fromSub($saleItems->unionAll($returnItems), 'u')
            ->select('id', 'product', 'type')
            ->selectRaw('SUM(sales_quantity) as sales_quantity')
            ->selectRaw('SUM(return_quantity) as return_quantity')
            ->selectRaw('SUM(sale_total) as sale_total')
            ->selectRaw('SUM(sale_return_total) as sale_return_total')
            ->selectRaw('SUM(sale_total) - SUM(sale_return_total) as net_amount')
            ->groupBy('id', 'product', 'type')
            ->orderByDesc('net_amount')
            ->limit(self::TOP_LIMIT)
            ->get()
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'name' => $r->product,
                'type' => $r->type,
                'sales_quantity' => round((float) $r->sales_quantity, 2),
                'return_quantity' => round((float) $r->return_quantity, 2),
                'sale_total' => round((float) $r->sale_total, 2),
                'sale_return_total' => round((float) $r->sale_return_total, 2),
                'net_amount' => round((float) $r->net_amount, 2),
            ])
            ->all();
    }
}
