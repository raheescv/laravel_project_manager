<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GetListAction
{
    public function execute(array $filters = [], int $perPage = 15, ?string $sortField = null, ?string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = Issue::query() ->with(['account:id,name,mobile', 'items.product:id,name']);

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySort($query, $sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    private function applySort(Builder $query, ?string $sortField, ?string $sortDirection): Builder
    {
        $dir = strtolower($sortDirection ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'date', 'created_at', 'account_id', 'no_of_items_out', 'no_of_items_in', 'balance'];
        if ($sortField && in_array($sortField, $allowed)) {
            $query->orderBy("issues.{$sortField}", $dir);
        } else {
            $query->orderByDesc('issues.date')->orderByDesc('issues.id');
        }

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $query
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $nested) use ($search): void {
                    $nested->where('remarks', 'like', "%{$search}%")
                        ->orWhereHas('account', function (Builder $aq) use ($search): void {
                            $aq->where('name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            })
            ->when(! empty($filters['account_id']), function (Builder $q) use ($filters): void {
                $q->where('account_id', $filters['account_id']);
            })
            ->when(! empty($filters['type']), function (Builder $q) use ($filters): void {
                $q->where('type', $filters['type']);
            })
            ->when(! empty($filters['from_date']), function (Builder $q) use ($filters): void {
                $q->whereDate('date', '>=', $filters['from_date']);
            })
            ->when(! empty($filters['to_date']), function (Builder $q) use ($filters): void {
                $q->whereDate('date', '<=', $filters['to_date']);
            });
    }
}
