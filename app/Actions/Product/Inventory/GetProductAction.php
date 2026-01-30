<?php

namespace App\Actions\Product\Inventory;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetProductAction
{
    private const ALLOWED_SORT_FIELDS = [
        'products.code' => 'products.code',
        'products.name' => 'products.name',
        'products.size' => 'products.size',
        'inventories.barcode' => 'inventories.barcode',
        'products.mrp' => 'products.mrp',
        'branches.name' => 'branches.name',
        'inventories.quantity' => 'inventories.quantity',
        'inventories.id' => 'inventories.id',
        'inventories.product_id' => 'inventories.product_id',
    ];

    public function execute(array $params): array
    {
        $filters = $this->normalizeFilters($params);

        $query = $this->buildQuery($filters);
        $query = $this->applySorting($query, $filters);

        $total = $this->getTotalCount($query, $filters);
        $rows = $this->getRows($query, $filters);
        $totalQuantity = $this->calculateTotalQuantity($query, $filters, $rows);

        return $this->formatResponse($rows, $total, $totalQuantity, $filters);
    }

    private function normalizeFilters(array $params): array
    {
        return [
            'limit' => (int) ($params['limit'] ?? 10),
            'page' => (int) ($params['page'] ?? 1),
            'productName' => trim($params['productName'] ?? ''),
            'productCode' => trim($params['productCode'] ?? ''),
            'productBarcode' => trim($params['productBarcode'] ?? ''),
            'branch_id' => $this->normalizeBranchIds($params['branch_id'] ?? null),
            'show_non_zero' => (bool) ($params['show_non_zero'] ?? false),
            'show_barcode_sku' => (bool) ($params['show_barcode_sku'] ?? false),
            'sortField' => $params['sortField'] ?? 'products.code',
            'sortDirection' => strtolower($params['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
        ];
    }

    private function normalizeBranchIds($branchIds): ?array
    {
        if (empty($branchIds)) {
            return null;
        }

        if (! is_array($branchIds)) {
            $branchIds = explode(',', $branchIds);
        }

        return array_map('intval', $branchIds);
    }

    private function buildQuery(array $filters): Builder
    {
        $query = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->where('products.type', '=', 'product');

        $this->applyProductNameFilter($query, $filters);
        $this->applyProductCodeFilter($query, $filters);
        $this->applyBarcodeFilter($query, $filters);
        $this->applyBranchFilter($query, $filters);
        $this->applyNonZeroFilter($query, $filters);
        $this->applyBarcodeSkuFilter($query, $filters);

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
        $query->where('inventories.branch_id', session('branch_id'));
    }

    private function applyNonZeroFilter(Builder $query, array $filters): void
    {
        if ($filters['show_non_zero']) {
            $query->where('quantity', '>', 0);
        }
    }

    private function applySorting(Builder $query, array $filters): Builder
    {
        $sortField = $filters['sortField'];

        if (! array_key_exists($sortField, self::ALLOWED_SORT_FIELDS)) {
            $sortField = 'products.code';
        }

        $sortField = self::ALLOWED_SORT_FIELDS[$sortField];
        $sortDirection = $filters['sortDirection'];

        // Joins are already applied in buildQuery, so we can directly order by
        $query->orderBy($sortField, $sortDirection);

        return $query;
    }

    private function getTotalCount(Builder $query, array $filters): int
    {
        // Total count for pagination (only if not barcode scan)
        return empty($filters['productBarcode']) ? (clone $query)->count() : 1;
    }

    private function getRows(Builder $query, array $filters): Collection
    {
        $query->select([
            'inventories.id as id',
            'products.id as product_id',
            'inventories.id as inventory_id',
            'products.code',
            'products.name',
            'products.size',
            'inventories.barcode',
            'products.mrp',
            'branches.id as branch_id',
            'branches.name as branch_name',
            'inventories.quantity',
        ]);

        if (empty($filters['productBarcode'])) {
            return $query->forPage($filters['page'], $filters['limit'])->get();
        }

        return $query->get();
    }

    private function calculateTotalQuantity(Builder $query, array $filters, Collection $rows): int
    {
        if (! empty($filters['productBarcode'])) {
            return (int) $rows->sum('quantity');
        }

        // Build quantity query with same filters
        $quantityQuery = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('products.type', 'product');

        if (! empty($filters['productName'])) {
            $quantityQuery->where('products.name', 'like', "%{$filters['productName']}%");
        }

        if (! empty($filters['productCode'])) {
            $quantityQuery->where('products.code', 'like', "%{$filters['productCode']}%");
        }

        if (! empty($filters['branch_id'])) {
            $quantityQuery->whereIn('inventories.branch_id', $filters['branch_id']);
        }

        if ($filters['show_non_zero']) {
            $quantityQuery->where('inventories.quantity', '>', 0);
        }

        if ($filters['show_barcode_sku']) {
            $quantityQuery->whereNotNull('inventories.barcode')
                ->where('inventories.barcode', '<>', '');
        }

        return (int) $quantityQuery->sum('inventories.quantity');
    }

    private function formatResponse(Collection $rows, int $total, int $totalQuantity, array $filters): array
    {
        $data = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'inventory_id' => $row->inventory_id,
                'product_id' => $row->product_id,
                'code' => $row->code,
                'name' => $row->name,
                'size' => $row->size,
                'barcode' => $row->barcode,
                'mrp' => $row->mrp,
                'branch_id' => $row->branch_id,
                'branch_name' => $row->branch_name,
                'quantity' => (int) $row->quantity,
            ];
        })->toArray();

        $lastPage = (int) ceil($total / max(1, $filters['limit']));

        return [
            'data' => $data,
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
