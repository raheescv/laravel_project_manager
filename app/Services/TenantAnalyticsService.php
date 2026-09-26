<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Headline numbers for one tenant, read by the super admin from inside a
 * different tenant. Every query drops the global scopes (tenant AND branch)
 * and filters by the target tenant explicitly, so what the viewer's own
 * session resolves to can never leak in.
 */
class TenantAnalyticsService
{
    public const CACHE_MINUTES = 5;

    /**
     * @return array{
     *     users: array{total: int, active: int, inactive: int, employees: int},
     *     branches: int,
     *     products: int,
     *     customers: int,
     *     sales: array{count: int, total: float, this_month: float, last_30_days: float},
     *     purchases: array{count: int, total: float},
     *     returns: array{count: int, total: float},
     *     last_activity: ?string,
     *     trend: list<array{month: string, label: string, total: float}>,
     *     top_branches: list<array{name: string, total: float, count: int}>,
     *     generated_at: string
     * }
     */
    public function summary(Tenant $tenant): array
    {
        return Cache::remember(self::cacheKey($tenant->id), now()->addMinutes(self::CACHE_MINUTES), fn () => $this->compute($tenant->id));
    }

    public static function forget(int $tenantId): void
    {
        Cache::forget(self::cacheKey($tenantId));
    }

    public static function cacheKey(int $tenantId): string
    {
        return "tenant_analytics:{$tenantId}";
    }

    private function compute(int $tenantId): array
    {
        $users = User::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->selectRaw('COUNT(*) as total, SUM(is_active = 1) as active, SUM(type = ?) as employees', ['employee'])
            ->first();

        $monthStart = now()->startOfMonth()->toDateString();
        $last30Start = now()->subDays(29)->toDateString();
        $sales = $this->completedSales($tenantId)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN date >= ? THEN grand_total ELSE 0 END), 0) as this_month', [$monthStart])
            ->selectRaw('COALESCE(SUM(CASE WHEN date >= ? THEN grand_total ELSE 0 END), 0) as last_30_days', [$last30Start])
            ->first();

        $purchases = Purchase::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'completed')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total')->first();

        $returns = SaleReturn::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'completed')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total')->first();

        $lastActivity = collect([
            Sale::withoutGlobalScopes()->where('tenant_id', $tenantId)->max('created_at'),
            Purchase::withoutGlobalScopes()->where('tenant_id', $tenantId)->max('created_at'),
            SaleReturn::withoutGlobalScopes()->where('tenant_id', $tenantId)->max('created_at'),
        ])->filter()->max();

        $userTotal = (int) $users->total;
        $userActive = (int) $users->active;

        return [
            'users' => ['total' => $userTotal, 'active' => $userActive, 'inactive' => $userTotal - $userActive, 'employees' => (int) $users->employees],
            'branches' => Branch::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'products' => Product::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->count(),
            'customers' => Account::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('model', 'customer')->count(),
            'sales' => [
                'count' => (int) $sales->count,
                'total' => (float) $sales->total,
                'this_month' => (float) $sales->this_month,
                'last_30_days' => (float) $sales->last_30_days,
            ],
            'purchases' => ['count' => (int) $purchases->count, 'total' => (float) $purchases->total],
            'returns' => ['count' => (int) $returns->count, 'total' => (float) $returns->total],
            'last_activity' => $lastActivity ? Carbon::parse($lastActivity)->toDateTimeString() : null,
            'trend' => $this->monthlyTrend($tenantId),
            'top_branches' => $this->topBranches($tenantId),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    private function completedSales(int $tenantId)
    {
        return Sale::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'completed');
    }

    /**
     * Twelve calendar months ending with the current one; months without
     * sales are present with a zero so the chart keeps its shape.
     *
     * @return list<array{month: string, label: string, total: float}>
     */
    private function monthlyTrend(int $tenantId): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $totals = $this->completedSales($tenantId)
            ->where('date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(grand_total) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect(range(0, 11))->map(function (int $offset) use ($start, $totals): array {
            $month = $start->copy()->addMonths($offset);

            return ['month' => $month->format('Y-m'), 'label' => $month->format('M y'), 'total' => (float) ($totals[$month->format('Y-m')] ?? 0)];
        })->all();
    }

    /**
     * @return list<array{name: string, total: float, count: int}>
     */
    private function topBranches(int $tenantId): array
    {
        $rows = $this->completedSales($tenantId)
            ->selectRaw('branch_id, COUNT(*) as count, SUM(grand_total) as total')
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $names = Branch::withoutGlobalScopes()->whereIn('id', $rows->pluck('branch_id'))->pluck('name', 'id');

        return $rows->map(fn ($row): array => [
            'name' => $names[$row->branch_id] ?? 'Unknown branch',
            'total' => (float) $row->total,
            'count' => (int) $row->count,
        ])->values()->all();
    }
}
