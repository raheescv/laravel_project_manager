<?php

namespace App\Services\Dashboard\Sources;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\TailoringPayment;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales chart with today's figures and payment split, and today's best
 * sellers. Sales and tailoring orders are added together, as before.
 * Ported from the Livewire `dashboard.sale.overview` and `dashboard.top-sale-items` components.
 */
class SalesPerformanceSource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('sale-overview', 'Sales', 'Sales overview', 'Sales performance', 'bar-chart', 'chart', 8, 6, ['sale.dashboard bar chart'], ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'], 'week'),
            new WidgetDefinition('top-sale-items', 'Sales', "Today's top picks", 'Best sellers today', 'star', 'table', 4, 4, ['sale.dashboard top items']),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return $key === 'top-sale-items' ? $this->topItems() : $this->overview((string) $option);
    }

    /**
     * @return array{blocks: list<array<string, mixed>>, link: array{label: string, url: string}|null}
     */
    protected function overview(string $period): array
    {
        $points = match ($period) {
            'day' => $this->dailyData(),
            'month' => $this->monthlyData(),
            'year' => $this->yearlyData(),
            default => $this->weeklyData(),
        };

        $todaySale = (float) Sale::completed()->currentBranch()->today()->sum('grand_total')
            + (float) $this->tailoringOrders()->whereDate('order_date', today())->sum('grand_total');
        $todayPayment = (float) SalePayment::completedSale()->currentBranch()->today()->sum('amount')
            + (float) $this->tailoringPayments()->whereDate('tailoring_payments.date', today())->sum('tailoring_payments.amount');
        $credit = $todaySale - $todayPayment;

        $highest = max(
            (float) (Sale::completed()->currentBranch()->today()->max('grand_total') ?? 0),
            (float) ($this->tailoringOrders()->whereDate('order_date', today())->max('grand_total') ?? 0),
        );
        $lowestCandidates = array_filter([
            (float) (Sale::completed()->currentBranch()->today()->min('grand_total') ?? 0),
            (float) ($this->tailoringOrders()->whereDate('order_date', today())->min('grand_total') ?? 0),
        ], fn (float $value): bool => $value > 0);
        $lowest = $lowestCandidates === [] ? 0.0 : min($lowestCandidates);

        $methods = $this->paymentMethods($credit);
        $total = $todayPayment + $credit;
        $tones = ['acc', 'c2', 'c3', 'c4', 'info', 'slate'];

        return [
            'blocks' => [
                Blocks::bars($points->pluck('date')->all(), [Blocks::series('Sales', 'acc', $points->pluck('amount')->all())], 'money', 168, gradient: true),
                Blocks::split(
                    [
                        Blocks::stat("Today's sales", $todaySale),
                        Blocks::panels([
                            ['label' => 'Lowest', 'value' => $lowest, 'format' => 'money', 'tone' => 'slate'],
                            ['label' => 'Highest', 'value' => $highest, 'format' => 'money', 'tone' => 'ok'],
                        ]),
                    ],
                    [
                        Blocks::meters($methods->values()->map(fn (array $method, int $index): array => [
                            'label' => $method['method'],
                            'value' => $method['amount'],
                            'format' => 'money',
                            'note' => $this->percentage($method['amount'], $total).'%',
                            'percent' => $this->percentage($method['amount'], $total),
                            'tone' => $tones[$index % count($tones)],
                        ])->all(), 'No payments yet today.'),
                    ],
                ),
            ],
            'link' => $this->link('View sales list', 'sale::index', 'sale.view'),
        ];
    }

    /**
     * @return Collection<int, array{method: string, amount: float}>
     */
    protected function paymentMethods(float $credit): Collection
    {
        $sales = SalePayment::completedSale()
            ->currentBranch()
            ->today()
            ->join('accounts', 'payment_method_id', '=', 'accounts.id')
            ->select('accounts.name as method')
            ->selectRaw('sum(amount) as amount')
            ->groupBy('payment_method_id')
            ->get();
        $tailoring = $this->tailoringPayments()
            ->join('accounts', 'tailoring_payments.payment_method_id', '=', 'accounts.id')
            ->whereDate('tailoring_payments.date', today())
            ->select('accounts.name as method')
            ->selectRaw('sum(tailoring_payments.amount) as amount')
            ->groupBy('tailoring_payments.payment_method_id', 'accounts.name')
            ->get();

        $methods = $sales->toBase()
            ->merge($tailoring)
            ->groupBy('method')
            ->map(fn (Collection $rows, string $method): array => ['method' => $method, 'amount' => (float) $rows->sum('amount')])
            ->values();

        if ($credit) {
            $methods->push(['method' => 'Credit', 'amount' => $credit]);
        }

        return $methods;
    }

    /**
     * @return array{blocks: list<array<string, mixed>>}
     */
    protected function topItems(): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.branch_id', session('branch_id'))
            ->where('sales.status', 'completed')
            ->whereDate('sale_items.created_at', today())
            ->selectRaw('products.name as item_name, SUM(sale_items.quantity) as total_quantity, SUM(sale_items.total) as total_amount')
            ->groupBy('products.name')
            ->get();
        $tailoringItems = TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_order_items.tailoring_order_id', '=', 'tailoring_orders.id')
            ->when(session('branch_id'), fn ($query) => $query->where('tailoring_orders.branch_id', session('branch_id')))
            ->whereDate('tailoring_order_items.created_at', today())
            ->selectRaw('COALESCE(NULLIF(tailoring_order_items.product_name, ""), "Tailoring Item") as item_name')
            ->selectRaw('SUM(tailoring_order_items.quantity) as total_quantity')
            ->selectRaw('SUM(COALESCE(tailoring_order_items.total, 0)) as total_amount')
            ->groupBy(DB::raw('COALESCE(NULLIF(tailoring_order_items.product_name, ""), "Tailoring Item")'))
            ->get();

        $rows = $saleItems->toBase()
            ->concat($tailoringItems)
            ->groupBy('item_name')
            ->map(fn (Collection $rows, string $name): array => ['name' => $name, 'quantity' => (float) $rows->sum('total_quantity'), 'amount' => (float) $rows->sum('total_amount')])
            ->sortByDesc('quantity')
            ->take(10)
            ->values()
            ->map(fn (array $row, int $index): array => [['rank' => $index + 1], $row['name'], $row['quantity'], $row['amount']])
            ->all();

        return ['blocks' => [Blocks::table([
            ['label' => '#'],
            ['label' => 'Item', 'strong' => true],
            ['label' => 'Qty', 'align' => 'end', 'format' => 'number'],
            ['label' => 'Amount', 'align' => 'end', 'format' => 'money'],
        ], $rows, 'No sales yet today.')]];
    }

    /**
     * @return Collection<int, array{date: string, amount: float}>
     */
    protected function dailyData(): Collection
    {
        $days = collect(range(29, 0, -1))->mapWithKeys(fn (int $ago): array => [now()->subDays($ago)->format('d-M') => 0.0]);
        $sales = Sale::completed()->currentBranch()->last30Days()
            ->selectRaw("DATE_FORMAT(date, '%d-%b') as day, SUM(grand_total) as amount")
            ->groupBy('day')->pluck('amount', 'day');
        $tailoring = $this->tailoringOrders()
            ->whereBetween('order_date', [now()->subDays(30)->toDateString(), today()->toDateString()])
            ->selectRaw("DATE_FORMAT(order_date, '%d-%b') as day, SUM(grand_total) as amount")
            ->groupBy('day')->pluck('amount', 'day');

        return $days->map(fn (float $zero, string $day): array => ['date' => $day, 'amount' => (float) ($sales[$day] ?? 0) + (float) ($tailoring[$day] ?? 0)])->values();
    }

    /**
     * @return Collection<int, array{date: string, amount: float}>
     */
    protected function weeklyData(): Collection
    {
        $sales = Sale::completed()->currentBranch()->last7Days()
            ->selectRaw("DATE_FORMAT(date, '%a') as day, SUM(grand_total) as amount")
            ->groupBy('day')->pluck('amount', 'day');
        $tailoring = $this->tailoringOrders()
            ->whereBetween('order_date', [now()->subDays(7)->toDateString(), today()->toDateString()])
            ->selectRaw("DATE_FORMAT(order_date, '%a') as day, SUM(grand_total) as amount")
            ->groupBy('day')->pluck('amount', 'day');

        return collect(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
            ->map(fn (string $day): array => ['date' => $day, 'amount' => (float) ($sales[$day] ?? 0) + (float) ($tailoring[$day] ?? 0)]);
    }

    /**
     * @return Collection<int, array{date: string, amount: float}>
     */
    protected function monthlyData(): Collection
    {
        $months = collect(range(11, 0, -1))->mapWithKeys(fn (int $ago): array => [now()->subMonths($ago)->format('Y-m') => now()->subMonths($ago)->format('M Y')]);
        $sales = Sale::completed()->currentBranch()->lastYear()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y-%m')"))->pluck('amount', 'month');
        $tailoring = $this->tailoringOrders()
            ->whereBetween('order_date', [now()->subMonths(11)->toDateString(), today()->toDateString()])
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') as month, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(order_date, '%Y-%m')"))->pluck('amount', 'month');

        return $months->map(fn (string $label, string $month): array => ['date' => $label, 'amount' => (float) ($sales[$month] ?? 0) + (float) ($tailoring[$month] ?? 0)])->values();
    }

    /**
     * @return Collection<int, array{date: string, amount: float}>
     */
    protected function yearlyData(): Collection
    {
        $sales = Sale::completed()->currentBranch()
            ->where('date', '>=', now()->subYears(10)->startOfYear())
            ->selectRaw("DATE_FORMAT(date, '%Y') as year, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y')"))->pluck('amount', 'year');
        $tailoring = $this->tailoringOrders()
            ->where('order_date', '>=', now()->subYears(10)->startOfYear())
            ->selectRaw("DATE_FORMAT(order_date, '%Y') as year, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(order_date, '%Y')"))->pluck('amount', 'year');

        return collect(range(5, 0, -1))
            ->map(fn (int $ago): string => now()->subYears($ago)->format('Y'))
            ->map(fn (string $year): array => ['date' => $year, 'amount' => (float) ($sales[$year] ?? 0) + (float) ($tailoring[$year] ?? 0)]);
    }

    /**
     * @return Builder<TailoringOrder>
     */
    protected function tailoringOrders(): Builder
    {
        return TailoringOrder::query()->when(session('branch_id'), fn ($query) => $query->where('branch_id', session('branch_id')));
    }

    /**
     * @return Builder<TailoringPayment>
     */
    protected function tailoringPayments(): Builder
    {
        return TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_payments.tailoring_order_id', '=', 'tailoring_orders.id')
            ->when(session('branch_id'), fn ($query) => $query->where('tailoring_orders.branch_id', session('branch_id')));
    }
}
