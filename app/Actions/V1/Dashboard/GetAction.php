<?php

namespace App\Actions\V1\Dashboard;

use App\Http\Requests\V1\Dashboard\IndexRequest;
use App\Models\Account;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnPayment;
use App\Models\User;

class GetAction
{
    /**
     * Build the admin overview dashboard: today's snapshot cards plus the
     * sales-overview block (Sales Performance + Payment Overview), matching
     * the /report/sales_overview screen.
     */
    public function execute(IndexRequest $request): array
    {
        $today = today()->toDateString();
        $filters = $request->validatedWithDefaults();

        return [
            'date' => $today,
            'cards' => $this->todayCards($today),
            'sales_overview' => $this->salesOverview($filters),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todayCards(string $today): array
    {
        $salesTotal = round((float) Sale::query()->completed()->whereDate('date', $today)->sum('paid'), 2);
        $billsCount = Sale::query()->completed()->whereDate('date', $today)->count();
        $employees = User::query()->employee()->where('is_active', true)->count();
        $customers = Account::query()->customer()->count();
        $products = Product::query()->product()->count();
        $services = Product::query()->service()->count();

        return [
            ['title' => "Today's Sales", 'value' => $salesTotal, 'type' => 'currency'],
            ['title' => "Today's Bills", 'value' => $billsCount, 'type' => 'count'],
            ['title' => 'Active Employees', 'value' => $employees, 'type' => 'count'],
            ['title' => 'Customers', 'value' => $customers, 'type' => 'count'],
            ['title' => 'Products', 'value' => $products, 'type' => 'count'],
            ['title' => 'Services', 'value' => $services, 'type' => 'count'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function salesOverview(array $filters): array
    {
        $from = $filters['from_date'];
        $to = $filters['to_date'];
        $branchId = $filters['branch_id'];

        $saleTotals = $this->saleTotals($from, $to, $branchId);
        $returnTotals = $this->returnTotals($from, $to, $branchId);
        $itemSplit = $this->itemSplit($from, $to, $branchId);
        $salePayments = $this->salePaymentsBreakdown($from, $to, $branchId);
        $returnPayments = $this->returnPaymentsBreakdown($from, $to, $branchId);

        $salesPaymentTotal = (float) $salePayments->sum('total');
        $returnsPaymentTotal = (float) $returnPayments->sum('total');
        $salesPaymentTxns = (int) $salePayments->sum('transaction_count');
        $returnsPaymentTxns = (int) $returnPayments->sum('transaction_count');

        $netPayment = $salesPaymentTotal - $returnsPaymentTotal;
        $totalTransactions = $salesPaymentTxns + $returnsPaymentTxns;

        $successRate = $saleTotals['no_of_sales'] > 0
            ? (($saleTotals['no_of_sales'] - $returnTotals['no_of_returns']) / $saleTotals['no_of_sales']) * 100
            : 0;

        $collectionRate = $saleTotals['total_sales'] > 0
            ? ($salesPaymentTotal / $saleTotals['total_sales']) * 100
            : 0;

        return [
            'period' => [
                'from_date' => $from,
                'to_date' => $to,
                'branch_id' => $branchId ? (string) $branchId : null,
            ],
            'sales_performance' => [
                'sales_success_rate' => round((float) $successRate, 2),
                'payment_methods' => $this->paymentMethodCards($salePayments, $returnPayments, $saleTotals, $returnTotals),
                'gross_sales' => round((float) $saleTotals['gross_sales'], 2),
                'discounts' => round((float) $saleTotals['discount'], 2),
                'net_sales' => round((float) $saleTotals['net_sales'], 2),
                'total_item' => round((float) $itemSplit['total'], 2),
                'products' => round((float) $itemSplit['product'], 2),
                'services' => round((float) $itemSplit['service'], 2),
            ],
            'payment_overview' => [
                'sales_payments' => [
                    'amount' => round($salesPaymentTotal, 2),
                    'transactions' => $salesPaymentTxns,
                ],
                'returns_payments' => [
                    'amount' => round($returnsPaymentTotal, 2),
                    'returns' => $returnsPaymentTxns,
                ],
                'net_payments' => [
                    'amount' => round($netPayment, 2),
                    'total_transactions' => $totalTransactions,
                ],
                'collection_rate' => round((float) $collectionRate, 2),
                'top_payment_methods' => $salePayments
                    ->take(3)
                    ->map(fn ($row) => [
                        'method' => $row->payment_method,
                        'amount' => round((float) $row->total, 2),
                        'transactions' => (int) $row->transaction_count,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * Per-payment-method cards: transactions, sales, returns, net.
     * Includes a synthetic "Credit" row for unpaid balances.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentMethodCards($salePayments, $returnPayments, array $saleTotals, array $returnTotals): array
    {
        $methods = $salePayments->pluck('payment_method')
            ->merge($returnPayments->pluck('payment_method'))
            ->unique()
            ->values();

        $rows = $methods->map(function ($method) use ($salePayments, $returnPayments) {
            $sale = (float) ($salePayments->firstWhere('payment_method', $method)->total ?? 0);
            $return = (float) ($returnPayments->firstWhere('payment_method', $method)->total ?? 0);
            $saleCount = (int) ($salePayments->firstWhere('payment_method', $method)->transaction_count ?? 0);
            $returnCount = (int) ($returnPayments->firstWhere('payment_method', $method)->transaction_count ?? 0);

            return [
                'method' => $method,
                'transactions' => $saleCount + $returnCount,
                'sales' => round($sale, 2),
                'returns' => round($return, 2),
                'net' => round($sale - $return, 2),
            ];
        })->all();

        $rows[] = [
            'method' => 'Credit',
            'transactions' => (int) $saleTotals['balance_count'] + (int) $returnTotals['balance_count'],
            'sales' => round((float) $saleTotals['balance'], 2),
            'returns' => round((float) $returnTotals['balance'], 2),
            'net' => round((float) $saleTotals['balance'] - (float) $returnTotals['balance'], 2),
        ];

        return $rows;
    }

    /**
     * @return array<string, float|int>
     */
    private function saleTotals(string $from, string $to, ?int $branchId): array
    {
        $row = Sale::query()
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->selectRaw('
                COALESCE(SUM(gross_amount), 0) as gross_sales,
                COALESCE(SUM(grand_total), 0) as net_sales,
                COALESCE(SUM(total), 0) as total_sales,
                COALESCE(SUM(balance), 0) as balance,
                COALESCE(SUM(other_discount), 0) + COALESCE(SUM(item_discount), 0) - COALESCE(SUM(round_off), 0) as discount,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as balance_count,
                COUNT(*) as no_of_sales
            ')
            ->first();

        return [
            'gross_sales' => (float) $row->gross_sales,
            'net_sales' => (float) $row->net_sales,
            'total_sales' => (float) $row->total_sales,
            'balance' => (float) $row->balance,
            'discount' => (float) $row->discount,
            'balance_count' => (int) $row->balance_count,
            'no_of_sales' => (int) $row->no_of_sales,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function returnTotals(string $from, string $to, ?int $branchId): array
    {
        $row = SaleReturn::query()
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->selectRaw('
                COALESCE(SUM(grand_total), 0) as total_returns,
                COALESCE(SUM(balance), 0) as balance,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as balance_count,
                COUNT(*) as no_of_returns
            ')
            ->first();

        return [
            'total_returns' => (float) $row->total_returns,
            'balance' => (float) $row->balance,
            'balance_count' => (int) $row->balance_count,
            'no_of_returns' => (int) $row->no_of_returns,
        ];
    }

    /**
     * Total item revenue split by product / service type.
     *
     * @return array<string, float>
     */
    private function itemSplit(string $from, string $to, ?int $branchId): array
    {
        $row = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.date', '>=', $from)
            ->whereDate('sales.date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->selectRaw("
                COALESCE(SUM(sale_items.total), 0) as total,
                COALESCE(SUM(CASE WHEN products.type = 'product' THEN sale_items.total ELSE 0 END), 0) as product_total,
                COALESCE(SUM(CASE WHEN products.type = 'service' THEN sale_items.total ELSE 0 END), 0) as service_total
            ")
            ->first();

        return [
            'total' => (float) $row->total,
            'product' => (float) $row->product_total,
            'service' => (float) $row->service_total,
        ];
    }

    private function salePaymentsBreakdown(string $from, string $to, ?int $branchId)
    {
        return SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->whereDate('sale_payments.date', '>=', $from)
            ->whereDate('sale_payments.date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_payments.sale_id) as transaction_count')
            ->groupBy('sale_payments.payment_method_id', 'accounts.name')
            ->orderByDesc('total')
            ->get();
    }

    private function returnPaymentsBreakdown(string $from, string $to, ?int $branchId)
    {
        return SaleReturnPayment::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_payments.sale_return_id')
            ->join('accounts', 'accounts.id', '=', 'sale_return_payments.payment_method_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->whereDate('sale_return_payments.date', '>=', $from)
            ->whereDate('sale_return_payments.date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('sale_returns.branch_id', $value))
            ->select('accounts.name as payment_method')
            ->selectRaw('SUM(sale_return_payments.amount) as total')
            ->selectRaw('COUNT(DISTINCT sale_return_payments.sale_return_id) as transaction_count')
            ->groupBy('sale_return_payments.payment_method_id', 'accounts.name')
            ->orderByDesc('total')
            ->get();
    }
}
