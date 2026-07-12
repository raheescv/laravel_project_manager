<?php

namespace App\Actions\Product\Inventory;

use App\Models\Branch;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetProductAction
{
    public function execute(array $params): array
    {
        $filters = $this->normalizeFilters($params);

        // Branches rendered as columns (respect the branch filter when set).
        $branchColumns = $this->getBranchColumns($filters);

        $base = $this->buildQuery($filters);

        $total = $this->getTotalCount($base, $filters);
        $rows = $this->getRows($base, $filters, $branchColumns);
        $totalQuantity = $this->calculateTotalQuantity($base, $filters);

        return $this->formatResponse($rows, $total, $totalQuantity, $filters, $branchColumns);
    }

    private function normalizeFilters(array $params): array
    {
        return [
            'limit' => (int) ($params['limit'] ?? 10),
            'page' => (int) ($params['page'] ?? 1),
            'productName' => trim($params['productName'] ?? ''),
            'productCode' => trim($params['productCode'] ?? ''),
            'productSize' => trim($params['productSize'] ?? ''),
            'productBarcode' => trim($params['productBarcode'] ?? ''),
            'inventory_ids' => $this->normalizeInventoryIds($params['inventory_ids'] ?? null),
            'branch_id' => $this->normalizeBranchIds($params['branch_id'] ?? null),
            'show_non_zero' => (bool) ($params['show_non_zero'] ?? false),
            'show_barcode_sku' => (bool) ($params['show_barcode_sku'] ?? false),
            'sortField' => $params['sortField'] ?? 'products.code',
            'sortDirection' => strtolower($params['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
        ];
    }

    private function normalizeInventoryIds($inventoryIds): ?array
    {
        if (empty($inventoryIds)) {
            return null;
        }

        if (! is_array($inventoryIds)) {
            $inventoryIds = explode(',', (string) $inventoryIds);
        }

        $normalizedIds = collect($inventoryIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return empty($normalizedIds) ? null : $normalizedIds;
    }

    private function normalizeBranchIds($branchIds): ?array
    {
        if (empty($branchIds)) {
            return null;
        }

        if (! is_array($branchIds)) {
            $branchIds = explode(',', $branchIds);
        }

        $ids = array_values(array_filter(array_map('intval', $branchIds)));

        return empty($ids) ? null : $ids;
    }

    /**
     * Branches shown as pivot columns. When the branch filter is set we only
     * show those branches; otherwise every branch of the tenant.
     */
    private function getBranchColumns(array $filters): Collection
    {
        $query = Branch::query()->orderBy('name');

        if (! empty($filters['branch_id'])) {
            $query->whereIn('id', $filters['branch_id']);
        }

        return $query->get(['id', 'name']);
    }

    private function buildQuery(array $filters): Builder
    {
        $query = Inventory::withoutGlobalScopes()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('products.type', '=', 'product');

        $this->applyProductNameFilter($query, $filters);
        $this->applyProductCodeFilter($query, $filters);
        $this->applyProductSizeFilter($query, $filters);
        $this->applyBarcodeFilter($query, $filters);
        $this->applyInventoryIdsFilter($query, $filters);
        $this->applyBranchFilter($query, $filters);
        $this->applyNonZeroFilter($query, $filters);

        return $query;
    }

    private function applyProductNameFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['productName'])) {
            $searchTerm = "%{$filters['productName']}%";
            $query->where(function ($q) use ($searchTerm) {
                $q->where('products.name', 'like', $searchTerm)
                    ->orWhere('products.code', 'like', $searchTerm);
            });
        }
    }

    private function applyProductCodeFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['productCode'])) {
            $query->where('products.code', $filters['productCode']);
        }
    }

    private function applyProductSizeFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['productSize'])) {
            $query->where('products.size', 'like', "%{$filters['productSize']}%");
        }
    }

    private function applyBarcodeFilter(Builder $query, array $filters): void
    {
        if (empty($filters['productBarcode'])) {
            return;
        }

        if ($filters['show_barcode_sku']) {
            $this->applyBarcodeSkuFilter($query, $filters);
        } else {
            $query->where('inventories.barcode', $filters['productBarcode']);
        }
    }

    private function applyBarcodeSkuFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['productBarcode']) && $filters['show_barcode_sku']) {
            $sku = Inventory::query()
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->where('inventories.barcode', $filters['productBarcode'])
                ->where('products.type', 'product')
                ->value('products.code');

            if ($sku) {
                $query->where('products.code', $sku);
            }
        } elseif ($filters['show_barcode_sku']) {
            $query->whereNotNull('inventories.barcode')
                ->where('inventories.barcode', '<>', '');
        }
    }

    private function applyBranchFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['branch_id'])) {
            $query->whereIn('inventories.branch_id', $filters['branch_id']);
        }
    }

    private function applyInventoryIdsFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['inventory_ids'])) {
            $query->whereIn('inventories.id', $filters['inventory_ids']);
        }
    }

    private function applyNonZeroFilter(Builder $query, array $filters): void
    {
        if ($filters['show_non_zero']) {
            $query->where('inventories.quantity', '>', 0);
        }
    }

    /**
     * Distinct products matching the filters (one product = one pivot row).
     */
    private function getTotalCount(Builder $base, array $filters): int
    {
        return (clone $base)->distinct()->count('products.id');
    }

    /**
     * One row per product with a conditional-sum column per branch.
     */
    private function getRows(Builder $base, array $filters, Collection $branchColumns): Collection
    {
        $query = clone $base;

        $branchSelects = $branchColumns->map(function ($branch) {
            $id = (int) $branch->id;

            return DB::raw("COALESCE(SUM(CASE WHEN inventories.branch_id = {$id} THEN inventories.quantity ELSE 0 END), 0) as branch_{$id}");
        })->all();

        $query->select(array_merge([
            DB::raw('MIN(inventories.id) as id'),
            'products.id as product_id',
            'products.code',
            'products.name',
            'products.size',
            DB::raw('MAX(inventories.barcode) as barcode'),
            'products.mrp',
            'products.thumbnail',
            DB::raw('SUM(inventories.quantity) as quantity'),
        ], $branchSelects))
            ->groupBy('products.id', 'products.code', 'products.name', 'products.size', 'products.mrp', 'products.thumbnail');

        $this->applySorting($query, $filters);

        return $query->forPage($filters['page'], $filters['limit'])->get();
    }

    private function applySorting(Builder $query, array $filters): void
    {
        $map = [
            'products.code' => 'products.code',
            'products.name' => 'products.name',
            'products.size' => 'products.size',
            'products.mrp' => 'products.mrp',
            'inventories.barcode' => 'barcode',
            'inventories.quantity' => 'quantity',
            // Branch is now a set of columns, so fall back to product name.
            'branches.name' => 'products.name',
        ];

        $field = $map[$filters['sortField']] ?? 'products.code';

        $query->orderBy($field, $filters['sortDirection']);
    }

    private function calculateTotalQuantity(Builder $base, array $filters): int
    {
        return (int) (clone $base)->sum('inventories.quantity');
    }

    private function formatResponse(Collection $rows, int $total, int $totalQuantity, array $filters, Collection $branchColumns): array
    {
        $branches = $branchColumns->map(fn ($branch) => [
            'id' => (int) $branch->id,
            'name' => $branch->name,
        ])->values()->all();

        $data = $rows->map(function ($row) use ($branchColumns) {
            $branchQuantities = [];
            foreach ($branchColumns as $branch) {
                $key = 'branch_'.((int) $branch->id);
                $branchQuantities[(int) $branch->id] = (int) ($row->{$key} ?? 0);
            }

            return [
                'id' => $row->id,
                'inventory_id' => $row->id,
                'product_id' => $row->product_id,
                'code' => $row->code,
                'name' => $row->name,
                'size' => $row->size,
                'barcode' => $row->barcode,
                'mrp' => $row->mrp,
                'quantity' => (int) $row->quantity,
                'branch_quantities' => $branchQuantities,
                'thumbnail' => $row->thumbnail,
            ];
        })->toArray();

        $lastPage = (int) ceil($total / max(1, $filters['limit']));

        return [
            'data' => $data,
            'branch_columns' => $branches,
            'total_quantity' => $totalQuantity,
            'links' => [
                'current_page' => $filters['page'],
                'last_page' => $lastPage,
            ],
            'per_page' => $filters['limit'],
            'total' => $total,
        ];
    }
}
