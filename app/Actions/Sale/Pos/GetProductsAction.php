<?php

namespace App\Actions\Sale\Pos;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\UniversalUnitConversion;
use App\Support\ProductColours;
use App\Support\Sale\OutOfStockSales;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetProductsAction
{
    /** One card per family: a variant's family is its parent, a plain product its own id. */
    private const FAMILY_KEY = 'COALESCE(products.parent_id, products.id)';

    private const CARD_LIMIT = 50;

    /**
     * Universal UOM rows for every base unit on the page, keyed by base_unit_id,
     * fetched once so a family of 300 variants does not resolve units 300 times.
     *
     * @var Collection<int, Collection<int, UniversalUnitConversion>>
     */
    private Collection $universalUnits;

    /** @var array{0: mixed}|null the tenant logo shown on cards without a photo, read once per request */
    private ?array $logo = null;

    public function execute(array $filters = [], ?int $branchId = null)
    {
        try {
            $branchId = $branchId ?? session('branch_id');
            $saleType = $filters['sale_type'] ?? 'normal';

            $isFavorite = ($filters['category_id'] ?? null) === 'favorite';
            $sort = appProductSort();
            $groupVariants = posGroupsVariants();

            $query = $this->baseQuery($filters, $branchId)
                ->when($isFavorite, fn ($q) => $groupVariants ? $this->favouriteFamilies($q) : $q->where('products.is_favorite', true))
                ->when($sort['by'] === 'priority', fn ($q) => $q->orderBy('products.priority', $sort['direction'])->orderBy('products.name'))
                ->when($sort['by'] === 'name', fn ($q) => $q->orderBy('products.name', $sort['direction']));

            $total = $groupVariants
                ? (clone $query)->count(DB::raw('DISTINCT '.self::FAMILY_KEY))
                : (clone $query)->count();

            if ($isFavorite && $total === 0 && empty($filters['search'])) {
                $query = $this->topSellingQuery($filters, $branchId);
                $rows = $query->get();
            } elseif ($groupVariants) {
                $familyIds = $this->firstFamilies($query, $sort, self::CARD_LIMIT);
                $rows = $query->where(fn ($q) => $q->whereIn('products.id', $familyIds)->orWhereIn('products.parent_id', $familyIds))->get();
            } else {
                $rows = $query->limit(self::CARD_LIMIT)->get();
            }

            $this->universalUnits = UniversalUnitConversion::with('subUnit')
                ->whereIn('base_unit_id', $rows->pluck('product.unit_id')->filter()->unique()->values())
                ->get()
                ->groupBy('base_unit_id');

            $palette = ProductColours::palette();
            $products = $groupVariants
                ? $this->groupFamilies($rows, $saleType, $palette)
                : $rows->map(fn ($inventory) => $this->formatProduct($inventory, $saleType, $palette))->values();
            $total = max($total, $products->count());

            return ['success' => true, 'data' => $products, 'total' => $total];
        } catch (\Exception $e) {
            Log::error('Error loading products: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function baseQuery(array $filters, ?int $branchId)
    {
        return Inventory::with(['product.unit', 'product.units.subUnit'])
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('categories', 'products.main_category_id', '=', 'categories.id')
            ->select('inventories.*')
            ->whereNull('inventories.employee_id')
            ->where('inventories.branch_id', $branchId)
            ->where('products.is_selling', true)
            ->where('categories.sale_visibility_flag', true)
            ->when(OutOfStockSales::hiddenFromSaleSelection(), fn ($q) => $q->where('inventories.quantity', '>', 0))
            ->when(! empty($filters['type']), fn ($q) => $q->where('products.type', $filters['type']))
            ->when(! empty($filters['category_id']) && $filters['category_id'] !== 'favorite', fn ($q) => $q->where('products.main_category_id', $filters['category_id']))
            ->when(! empty($filters['search']), function ($q) use ($filters): void {
                $search = trim($filters['search']);
                $q->where(fn ($s) => $s->where('products.name', 'LIKE', "%{$search}%")->orWhere('products.barcode', 'LIKE', "%{$search}%"));
            });
    }

    /**
     * The families on the first page of cards, in the till's sort order. The
     * limit counts cards, not stock rows — counting rows let one shoe in 32
     * sizes and colours push every other product off the grid.
     *
     * @param  array{by: string, direction: string}  $sort
     * @return array<int, int>
     */
    private function firstFamilies($query, array $sort, int $limit): array
    {
        $direction = $sort['direction'] === 'desc' ? 'desc' : 'asc';

        return (clone $query)
            ->reorder()
            ->selectRaw(self::FAMILY_KEY.' as family_id')
            ->groupByRaw(self::FAMILY_KEY)
            ->when($sort['by'] === 'priority', fn ($q) => $q->orderByRaw("MIN(products.priority) {$direction}"))
            ->orderByRaw('MIN(products.name) '.($sort['by'] === 'name' ? $direction : 'asc'))
            ->limit($limit)
            ->toBase()
            ->pluck('family_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Favorites work per family: starring a product brings its size / colour
     * variants along (they are one card at the till), and starring one variant
     * brings its family.
     */
    private function favouriteFamilies($query): void
    {
        $query->whereIn(
            DB::raw(self::FAMILY_KEY),
            Product::query()->selectRaw('COALESCE(parent_id, id)')->where('is_favorite', true),
        );
    }

    /**
     * Fallback for an empty Favorites list: the branch's ten best-selling items by completed-sale quantity.
     */
    private function topSellingQuery(array $filters, ?int $branchId)
    {
        $soldQuantities = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) as sold_quantity');

        $topInventoryIds = $this->baseQuery($filters, $branchId)
            ->joinSub($soldQuantities, 'sold', 'sold.product_id', '=', 'products.id')
            ->orderByDesc('sold.sold_quantity')
            ->limit(10)
            ->pluck('inventories.id');

        return $this->baseQuery($filters, $branchId)
            ->whereIn('inventories.id', $topInventoryIds)
            ->joinSub($soldQuantities, 'sold', 'sold.product_id', '=', 'products.id')
            ->orderByDesc('sold.sold_quantity');
    }

    /**
     * Size / colour variants of one product sell as separate stock rows but show
     * as ONE card: the card carries its `variants` and the till asks for the size
     * and colour. A family with a single row on the shelf stays a plain card.
     */
    private function groupFamilies(Collection $rows, string $saleType, array $palette): Collection
    {
        return $rows
            ->groupBy(fn ($inventory) => $inventory->product->parent_id ?: $inventory->product_id)
            ->map(function (Collection $members) use ($saleType, $palette): array {
                $variants = $members->map(fn ($inventory) => $this->formatProduct($inventory, $saleType, $palette))->values();
                if ($variants->count() === 1) {
                    return $variants->first();
                }
                $head = $members->first(fn ($inventory) => ! $inventory->product->parent_id) ?? $members->first();
                $prices = $variants->pluck('mrp')->map(fn ($price) => (float) $price);

                return array_merge($this->formatProduct($head, $saleType, $palette), [
                    'size' => null,
                    'barcode' => null,
                    'color' => null,
                    'color_name' => null,
                    'color_hex' => null,
                    'stock' => $variants->sum(fn ($variant) => (float) $variant['stock']),
                    'mrp' => $prices->min(),
                    'mrp_max' => $prices->max(),
                    'original_price' => $prices->min(),
                    'sizes' => $variants->pluck('size')->filter(fn ($size) => trim((string) $size) !== '')->unique()->values()->all(),
                    'colours' => $variants->filter(fn ($variant) => $variant['color'])->unique('color')
                        ->map(fn ($variant) => ['value' => $variant['color'], 'name' => $variant['color_name'], 'hex' => $variant['color_hex']])
                        ->values()->all(),
                    'variants' => $variants->all(),
                ]);
            })
            ->values();
    }

    private function logo(): mixed
    {
        $this->logo ??= [tenant_cache('logo')];

        return $this->logo[0];
    }

    private function formatProduct($inventory, string $saleType, array $palette = []): array
    {
        $product = $inventory->product;
        $color = trim((string) $product->color);

        return [
            'color' => $color === '' ? null : $color,
            'color_name' => $color === '' ? null : ProductColours::displayName($color),
            'color_hex' => $color === '' ? null : ProductColours::hexFor($color, $palette ?: null),
            'id' => $inventory->id,
            'name' => $product->name,
            'type' => $product->type,
            'barcode' => $product->barcode,
            'size' => $product->size,
            'code' => $product->code,
            'priority' => (int) $product->priority,
            'mrp' => $product->saleTypePrice($saleType),
            'original_price' => (float) $product->mrp,
            'stock' => $inventory->quantity ?? 0,
            'category_id' => $product->main_category_id,
            'product_id' => $inventory->product_id,
            'branch_id' => $inventory->branch_id,
            'image' => $product->thumbnail ?? $this->logo(),
            'photo' => $product->thumbnail ?: null,
            'unit_id' => $product->unit_id,
            'unit_name' => $product->unit->name ?? '',
            'conversion_factor' => 1,
            'units' => $product->resolvedUnitsWith($this->universalUnits->get($product->unit_id, collect())),
        ];
    }
}
