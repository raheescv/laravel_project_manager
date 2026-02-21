<?php

namespace App\Traits\Report;

use App\Models\TailoringOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait BuildsTailoringNonDeliveryQuery
{
    protected function nonDeliveryDateColumn(array $filters): string
    {
        return ($filters['date_type'] ?? 'order_date') === 'delivery_date'
            ? 'tailoring_orders.delivery_date'
            : 'tailoring_orders.order_date';
    }

    protected function normalizedNonDeliveryStatuses(array $filters): array
    {
        return array_values(array_filter((array) ($filters['status'] ?? [])));
    }

    protected function nonDeliveryBaseQuery(array $filters, array $allowedBranchIds): Builder
    {
        $statuses = $this->normalizedNonDeliveryStatuses($filters);
        $dateColumn = $this->nonDeliveryDateColumn($filters);
        $search = trim((string) ($filters['search'] ?? ''));

        return TailoringOrder::query()
            ->join('tailoring_order_items', 'tailoring_order_items.tailoring_order_id', '=', 'tailoring_orders.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'tailoring_orders.account_id')
            ->when($filters['from_date'] ?? '', fn ($q) => $q->whereDate($dateColumn, '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? '', fn ($q) => $q->whereDate($dateColumn, '<=', $filters['to_date']))
            ->when($filters['branch_id'] ?? '', fn ($q) => $q->where('tailoring_orders.branch_id', $filters['branch_id']))
            ->when($filters['customer_id'] ?? '', fn ($q) => $q->where('tailoring_orders.account_id', $filters['customer_id']))
            ->when($filters['category_id'] ?? '', fn ($q) => $q->where('tailoring_order_items.tailoring_category_id', $filters['category_id']))
            ->when(! empty($statuses), fn ($q) => $q->whereIn('tailoring_orders.status', $statuses))
            ->when($search !== '', function ($q) use ($search) {
                return $q->where(function ($q) use ($search): void {
                    $q->where('tailoring_orders.order_no', 'like', "%{$search}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$search}%")
                        ->orWhere('tailoring_orders.customer_mobile', 'like', "%{$search}%")
                        ->orWhere('accounts.name', 'like', "%{$search}%")
                        ->orWhere('accounts.mobile', 'like', "%{$search}%");
                });
            })
            ->whereIn('tailoring_orders.branch_id', $allowedBranchIds);
    }

    protected function nonDeliveryRowsQuery(array $filters, array $allowedBranchIds): Builder
    {
        return $this->nonDeliveryBaseQuery($filters, $allowedBranchIds)
            ->selectRaw('
                tailoring_orders.id,
                tailoring_orders.order_no,
                tailoring_orders.order_date,
                tailoring_orders.delivery_date,
                tailoring_orders.status as order_status,
                tailoring_orders.grand_total as bill_amount,
                tailoring_orders.paid as paid_amount,
                tailoring_orders.balance as balance_amount,
                COALESCE(accounts.name, tailoring_orders.customer_name) as customer_name,
                COALESCE(NULLIF(accounts.mobile, ""), tailoring_orders.customer_mobile) as customer_mobile,
                COALESCE(SUM(tailoring_order_items.quantity), 0) as item_quantity,
                COALESCE(SUM(tailoring_order_items.completed_quantity), 0) as completed_qty,
                COALESCE(SUM(tailoring_order_items.pending_quantity), 0) as pending_qty,
                COALESCE(SUM(tailoring_order_items.delivered_quantity), 0) as delivery_qty
            ')
            ->groupBy(
                'tailoring_orders.id',
                'tailoring_orders.order_no',
                'tailoring_orders.order_date',
                'tailoring_orders.delivery_date',
                'tailoring_orders.status',
                'tailoring_orders.grand_total',
                'tailoring_orders.paid',
                'tailoring_orders.balance',
                'accounts.name',
                'accounts.mobile',
                'tailoring_orders.customer_name',
                'tailoring_orders.customer_mobile'
            );
    }

    protected function nonDeliveryTotals(array $filters, array $allowedBranchIds): array
    {
        $rowsSubQuery = $this->nonDeliveryRowsQuery($filters, $allowedBranchIds);
        $totals = DB::query()->fromSub($rowsSubQuery, 'rows')
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(item_quantity), 0) as item_quantity,
                COALESCE(SUM(completed_qty), 0) as completed_qty,
                COALESCE(SUM(pending_qty), 0) as pending_qty,
                COALESCE(SUM(delivery_qty), 0) as delivery_qty,
                COALESCE(SUM(bill_amount), 0) as bill_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(balance_amount), 0) as balance_amount
            ')
            ->first();

        return [
            'total_orders' => (int) ($totals->total_orders ?? 0),
            'item_quantity' => (float) ($totals->item_quantity ?? 0),
            'completed_qty' => (float) ($totals->completed_qty ?? 0),
            'pending_qty' => (float) ($totals->pending_qty ?? 0),
            'delivery_qty' => (float) ($totals->delivery_qty ?? 0),
            'bill_amount' => (float) ($totals->bill_amount ?? 0),
            'paid_amount' => (float) ($totals->paid_amount ?? 0),
            'balance_amount' => (float) ($totals->balance_amount ?? 0),
        ];
    }

    protected function nonDeliverySortField(string $sortField): string
    {
        $allowed = [
            'tailoring_orders.order_no',
            'tailoring_orders.order_date',
            'tailoring_orders.delivery_date',
            'customer_name',
            'customer_mobile',
            'item_quantity',
            'bill_amount',
            'paid_amount',
            'balance_amount',
            'completed_qty',
            'pending_qty',
            'delivery_qty',
            'tailoring_orders.status',
        ];

        return in_array($sortField, $allowed, true) ? $sortField : 'tailoring_orders.order_date';
    }
}
