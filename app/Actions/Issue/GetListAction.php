<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GetListAction
{
    public function execute(array $filters = [], int $perPage = 15, ?string $sortField = null, ?string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = Issue::query()
            ->with(['account:id,name,mobile', 'items.product:id,name']);

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySort($query, $sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    private function applySort(Builder $query, ?string $sortField, ?string $sortDirection): Builder
    {
        $dir = strtolower($sortDirection ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'created_at', 'account_id', 'no_of_items_out', 'no_of_items_in', 'balance'];
        if ($sortField && in_array($sortField, $allowed)) {
            $query->orderBy("issues.{$sortField}", $dir);
        } else {
            $query->orderByDesc('issues.created_at')->orderByDesc('issues.id');
        }

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('remarks', 'like', "%{$search}%")
                    ->orWhereHas('account', function (Builder $aq) use ($search): void {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }
}
