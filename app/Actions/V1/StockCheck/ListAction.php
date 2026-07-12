<?php

namespace App\Actions\V1\StockCheck;

use App\Models\StockCheck;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * List stock checks for the mobile app with per-check progress (counted /
 * total / variance / net difference) and pagination. "Counted" mirrors the web
 * feature: an item is counted once it has been marked completed.
 */
class ListAction
{
    public function execute(Request $request): array
    {
        $branchId = $request->integer('branch_id') ?: null;
        $status = trim((string) $request->get('status'));
        $search = trim((string) $request->get('search'));
        $perPage = (int) $request->get('per_page', 15);

        $query = StockCheck::query()
            ->with(['branch:id,name', 'createdBy:id,name'])
            ->withCount([
                'items as items_total',
                'items as items_counted' => fn (Builder $q) => $q->where('status', 'completed'),
                'items as variance_count' => fn (Builder $q) => $q->where('status', 'completed')->where('difference', '!=', 0),
            ])
            ->withSum(['items as net_difference' => fn (Builder $q) => $q->where('status', 'completed')], 'difference')
            ->when($branchId, fn (Builder $q, $v) => $q->where('branch_id', $v))
            ->when($status, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($search, fn (Builder $q, $v) => $q->where(function (Builder $w) use ($v) {
                $w->where('title', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%");
            }))
            ->orderByDesc('created_at');

        $checks = $query->paginate($perPage);

        return [
            'data' => collect($checks->items())->map(fn (StockCheck $c) => $this->transform($c))->all(),
            'pagination' => [
                'current_page' => $checks->currentPage(),
                'last_page' => $checks->lastPage(),
                'per_page' => $checks->perPage(),
                'total' => $checks->total(),
                'has_more_pages' => $checks->hasMorePages(),
            ],
        ];
    }

    private function transform(StockCheck $c): array
    {
        $total = (int) ($c->items_total ?? 0);
        $counted = (int) ($c->items_counted ?? 0);

        return [
            'id' => $c->id,
            'title' => $c->title,
            'date' => $c->date,
            'description' => $c->description,
            'status' => $c->status,
            'branch_id' => $c->branch_id,
            'branch_name' => $c->branch?->name,
            'created_by' => $c->createdBy?->name,
            'items_total' => $total,
            'items_counted' => $counted,
            'variance_count' => (int) ($c->variance_count ?? 0),
            'net_difference' => round((float) ($c->net_difference ?? 0), 2),
            'progress' => $total > 0 ? round($counted / $total, 4) : 0,
        ];
    }
}
