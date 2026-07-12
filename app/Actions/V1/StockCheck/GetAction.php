<?php

namespace App\Actions\V1\StockCheck;

use App\Models\StockCheck;

/**
 * The stock check header + progress aggregates (counted / total / variance /
 * net difference) used by the mobile counting screen.
 */
class GetAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(int $id): array
    {
        $check = StockCheck::with(['branch:id,name', 'createdBy:id,name'])
            ->withCount([
                'items as items_total',
                'items as items_counted' => fn ($q) => $q->where('status', 'completed'),
                'items as variance_count' => fn ($q) => $q->where('status', 'completed')->where('difference', '!=', 0),
            ])
            ->withSum(['items as net_difference' => fn ($q) => $q->where('status', 'completed')], 'difference')
            ->findOrFail($id);

        $total = (int) ($check->items_total ?? 0);
        $counted = (int) ($check->items_counted ?? 0);

        return [
            'id' => $check->id,
            'title' => $check->title,
            'date' => $check->date,
            'description' => $check->description,
            'status' => $check->status,
            'branch_id' => $check->branch_id,
            'branch_name' => $check->branch?->name,
            'created_by' => $check->createdBy?->name,
            'items_total' => $total,
            'items_counted' => $counted,
            'variance_count' => (int) ($check->variance_count ?? 0),
            'net_difference' => round((float) ($check->net_difference ?? 0), 2),
            'progress' => $total > 0 ? round($counted / $total, 4) : 0,
        ];
    }
}
