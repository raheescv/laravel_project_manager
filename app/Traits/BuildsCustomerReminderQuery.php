<?php

namespace App\Traits;

use App\Models\Account;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

trait BuildsCustomerReminderQuery
{
    protected function getSaleProductIdsFromFilters(array $filters): SupportCollection
    {
        $productId = $filters['product_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;

        if ($productId) {
            return collect([$productId]);
        }

        $query = Product::query()->select('id');
        if ($categoryId) {
            $query->where('main_category_id', $categoryId);
        }

        return $query->pluck('id');
    }

    public function buildCustomerReminderListQuery(array $filters): Builder
    {
        $saleProductIds = $this->getSaleProductIdsFromFilters($filters);
        if ($saleProductIds->isEmpty()) {
            return Account::query()->whereRaw('1 = 0'); // Return no results if no products match
        }

        $branchId = $filters['branch_id'] ?? null;
        $reminderCutoffDate = $filters['reminder_cutoff_date'] ?? Carbon::now()->subDays(30)->toDateString();

        // Subquery for sales totals
        $salesSubQuery = DB::table('sales')
            ->select([
                'account_id',
                DB::raw('COUNT(*) as total_purchases'),
                DB::raw('SUM(grand_total) as total_spent'),
                DB::raw('MAX(date) as last_purchase_date'),
            ])
            ->where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('account_id');

        // Get customers who purchased sale items BEFORE the cutoff date
        $customersWithPastPurchases = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereIn('sale_items.product_id', $saleProductIds->all())
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.date', '<=', $reminderCutoffDate)
            ->distinct()
            ->pluck('sales.account_id');

        // Get customers who purchased sale items AFTER the cutoff date
        $customersWithRecentSalePurchases = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereIn('sale_items.product_id', $saleProductIds->all())
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.date', '>', $reminderCutoffDate)
            ->distinct()
            ->pluck('sales.account_id');

        $customerIdsNeedingReminders = $customersWithPastPurchases->diff($customersWithRecentSalePurchases);

        if ($customerIdsNeedingReminders->isEmpty()) {
            return Account::query()->whereRaw('1 = 0');
        }

        $query = Account::query()
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.mobile',
                'accounts.email',
                'accounts.nationality',
                'accounts.created_at',
                'derived_sales.total_purchases',
                'derived_sales.total_spent',
                'derived_sales.last_purchase_date',
            ])
            ->leftJoinSub($salesSubQuery, 'derived_sales', function ($join): void {
                $join->on('accounts.id', '=', 'derived_sales.account_id');
            })
            ->whereIn('accounts.id', $customerIdsNeedingReminders->all());

        if (! empty($filters['customer_id'])) {
            $query->where('accounts.id', $filters['customer_id']);
        }
        if (! empty($filters['nationality'])) {
            $query->where('accounts.nationality', $filters['nationality']);
        }
        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('accounts.name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('accounts.mobile', 'like', '%'.$searchTerm.'%')
                    ->orWhere('accounts.email', 'like', '%'.$searchTerm.'%');
            });
        }

        $priorityFilter = $filters['priority'] ?? 'all';
        if ($priorityFilter !== 'all') {
            $havingClause = match ($priorityFilter) {
                'high' => '(derived_sales.last_purchase_date IS NOT NULL AND ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) > 90)',
                'medium' => '(derived_sales.last_purchase_date IS NOT NULL AND ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) > 60 AND ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) <= 90)',
                'low' => '(derived_sales.last_purchase_date IS NOT NULL AND ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) > 30 AND ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) <= 60)',
                'recent' => '(derived_sales.last_purchase_date IS NULL OR (derived_sales.last_purchase_date IS NOT NULL AND ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) < 30))',
                default => '1=1'
            };
            if ($priorityFilter !== 'all') {
                $query->havingRaw($havingClause);
            }
        }

        $sortField = $filters['sort_field'] ?? 'derived_sales.last_purchase_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $dbSortableFields = ['name', 'email', 'mobile', 'nationality', 'created_at'];
        if (in_array($sortField, $dbSortableFields)) {
            $query->orderBy('accounts.'.$sortField, $sortDirection);
        } elseif (in_array($sortField, ['last_purchase_date', 'total_purchases', 'total_spent'])) {
            if ($sortField === 'last_purchase_date') {
                if (strtolower($sortDirection) === 'desc') {
                    $query->orderByRaw('derived_sales.last_purchase_date IS NULL ASC, derived_sales.last_purchase_date DESC');
                } else {
                    $query->orderByRaw('derived_sales.last_purchase_date IS NULL DESC, derived_sales.last_purchase_date ASC');
                }
            } else {
                $query->orderBy('derived_sales.'.$sortField, $sortDirection);
            }
        } elseif ($sortField === 'days_since_purchase') {
            $orderByExpression = 'CASE WHEN derived_sales.last_purchase_date IS NULL THEN '.(strtolower($sortDirection) === 'desc' ? -1 : 999999).' ELSE ABS(DATEDIFF(CURDATE(), derived_sales.last_purchase_date)) END';
            $query->orderByRaw("$orderByExpression $sortDirection");
        } else {
            $query->orderByRaw('COALESCE(derived_sales.last_purchase_date, "1900-01-01") '.$sortDirection);
        }

        return $query;
    }
}
