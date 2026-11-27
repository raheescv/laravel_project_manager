<?php

namespace App\Actions\V1\Product;

use App\Http\Requests\V1\GetProductsRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\TenantService;
use App\Support\ProductColours;
use App\Support\ProductOptions;
use App\Support\Storefront\SizeFilter;
use App\Support\StorefrontSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetProductsAction
{
    /**
     * How long a filtered result count is reused for. The mobile catalog
     * snapshot walks every page back to back and each page would otherwise
     * repeat the same COUNT over the whole catalog — by far the most expensive
     * query in the request once the row lookups are indexed.
     */
    private const COUNT_CACHE_TTL = 60;

    /** A size / colour variant's family is its parent; a plain product is its own family. */
    private const FAMILY_KEY = 'COALESCE(products.parent_id, products.id)';

    /** Filters that decide whether a family member is on offer at all — not which one the shopper is after. */
    private const FAMILY_MEMBER_FILTERS = ['type', 'branch_id', 'in_stock_only', 'online_only'];

    /**
     * Columns the list card actually renders. `products` has ~60 columns
     * (accounting links, depreciation, barcode parts, …); selecting them all
     * hydrated a wide model per row for fields the response never emits.
     */
    private const LIST_COLUMNS = [
        'id', 'parent_id', 'type', 'code', 'name', 'name_arabic', 'thumbnail', 'description',
        'barcode', 'color', 'size', 'variant_option', 'variant_size_option', 'variant_value', 'model', 'hsn_code', 'mrp', 'tax',
        'location', 'priority', 'time', 'document_file',
        'reorder_level', 'min_stock',
        'unit_id', 'brand_id', 'main_category_id', 'sub_category_id',
        'created_at', 'updated_at',
    ];

    /**
     * Execute the action to get products with filtering and pagination.
     */
    public function execute(GetProductsRequest $request): array
    {
        $filters = $request->validatedWithDefaults();
        $branchId = $filters['branch_id'] ?? null;
        if (filled($filters['brand'] ?? null)) {
            // The showcase names the brand ("new-balance"); a name that matches no brand matches no products.
            $filters['brand_id'] = StorefrontSlug::resolve(Brand::query(), $filters['brand'])?->id ?? 0;
        }

        $query = Product::query()->select(self::LIST_COLUMNS)->with([
            'unit:id,name,code',
            'brand:id,name,image_path',
            'mainCategory:id,name',
            'subCategory:id,name',
        ])->withOnlinePrice();

        $this->addThumbnailFallback($query);
        $this->addStockAggregates($query, $branchId);
        $this->addSpinFrameCount($query);

        $this->applyFilters($query, $filters);
        if ($filters['group_by_family'] ?? false) {
            $this->onePerFamily($query, $filters);
        }
        $this->applySorting($query, $filters);

        $products = $this->paginate($query, $filters);
        if ($filters['group_by_family'] ?? false) {
            $this->attachFamilies($products->getCollection(), $filters);
        }
        // The till asks these when the card is tapped (base, flavour, add-ons…); one lookup for the page.
        $optionGroups = ProductOptions::forProducts($products->getCollection());
        $products->getCollection()->each(fn (Product $product) => $product->setAttribute('option_groups_payload', $optionGroups[$product->id] ?? []));

        return [
            'data' => ProductResource::collection($products->items()),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
                'has_more_pages' => $products->hasMorePages(),
            ],
            'filters_applied' => array_filter($filters, function ($value, $key) {
                return ! in_array($key, ['sort_by', 'sort_direction', 'per_page', 'page']) && $value !== null;
            }, ARRAY_FILTER_USE_BOTH),
        ];
    }

    /**
     * Resolve the card photo without loading the images relation.
     *
     * The card only ever shows one image, so a correlated subquery for the
     * first normal image beats eager-loading every normal image row for the
     * page and then discarding all but the first.
     */
    private function addThumbnailFallback(Builder $query): void
    {
        $query->addSelect(['fallback_image_path' => ProductImage::query()
            ->select('path')
            ->whereColumn('product_images.product_id', 'products.id')
            ->where('method', 'normal')
            ->orderBy('id')
            ->limit(1),
        ]);
    }

    /**
     * How many 360° frames sit behind each row.
     *
     * The frames themselves are a detail-view payload — a spin is two dozen
     * images and the list must never carry them — but a card still has to know
     * whether there is a spin to badge, and the showcase's "has a 360° view"
     * filter has to be answerable. A count over the (product_id, method) index
     * is one subquery in the same round trip, and the images are never loaded.
     */
    private function addSpinFrameCount(Builder $query): void
    {
        $query->withCount(['images as spin_frame_count' => function ($q) {
            $q->where('method', 'angle');
        }]);
    }

    /**
     * Stock for the card badges, as SQL aggregates.
     *
     * The list shows a total and an availability badge — never the per-branch
     * breakdown — so summing in the database avoids hydrating every inventory
     * row (and its branch) for every product on the page.
     */
    private function addStockAggregates(Builder $query, ?int $branchId): void
    {
        $query->withSum('inventories as stock_total', 'quantity');

        if ($branchId) {
            $query->withSum([
                'inventories as stock_in_branch' => fn ($q) => $q->where('branch_id', $branchId),
            ], 'quantity');
        }
    }

    /**
     * Paginate, reusing a recently computed total for the same filter set.
     *
     * Mirrors what `->paginate()` returns; only the COUNT is served from cache.
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        $perPage = (int) $filters['per_page'];
        $page = (int) ($filters['page'] ?? Paginator::resolveCurrentPage());

        $total = $this->cachedTotal($query, $filters);

        $items = $total > 0
            ? $query->forPage($page, $perPage)->get()
            : $query->getModel()->newCollection();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * The row count for this filter set, cached briefly per tenant.
     *
     * Only pagination metadata is derived from it, so a count that lags a sale
     * or a new product by up to a minute is harmless — while recomputing it on
     * every page of a full catalog sync is not.
     */
    private function cachedTotal(Builder $query, array $filters): int
    {
        $count = fn () => $query->toBase()->getCountForPagination();

        // Free text would mint a cache key per keystroke — on the file store
        // those entries are only ever pruned when something reads them again.
        // The facets below come from a bounded set of buttons, so their keys
        // are reused; a search is counted fresh.
        if (! empty($filters['search'])) {
            return (int) $count();
        }

        $signature = $filters;
        unset($signature['page'], $signature['per_page']);
        ksort($signature);

        $tenantId = app(TenantService::class)->getCurrentTenantId() ?? 'none';
        $key = "v1_products_count:{$tenantId}:".md5(json_encode($signature));

        return (int) Cache::remember($key, self::COUNT_CACHE_TTL, $count);
    }

    /**
     * Keep one row per family: the head when it matches the filters, else the
     * first matching variant. A filter such as a size still picks the row — a
     * shopper browsing size 42 lands on the 42 — but the family is listed once.
     *
     * @param  Builder<Product>  $query
     */
    private function onePerFamily(Builder $query, array $filters): void
    {
        $matching = Product::query();
        $this->applyFilters($matching, $filters);

        $query->whereIn('products.id', $matching
            ->selectRaw('COALESCE(MIN(CASE WHEN products.parent_id IS NULL THEN products.id END), MIN(products.id))')
            ->groupByRaw(self::FAMILY_KEY)
            ->toBase());
    }

    /**
     * What a family card needs beyond its own row: every size and colour on
     * offer, the price range and the stock across the family. One query for
     * the page's families.
     *
     * @param  Collection<int, Product>  $products
     */
    private function attachFamilies(Collection $products, array $filters): void
    {
        if ($products->isEmpty()) {
            return;
        }
        $familyOf = fn (Product $product): int => (int) ($product->parent_id ?: $product->id);

        $members = Product::query()->select(['id', 'parent_id', 'variant_value', 'size', 'color', 'mrp'])->withOnlinePrice();
        $this->addStockAggregates($members, $filters['branch_id'] ?? null);
        $this->applyFilters($members, array_intersect_key($filters, array_flip(self::FAMILY_MEMBER_FILTERS)));
        $members = $members->whereIn(DB::raw(self::FAMILY_KEY), $products->map($familyOf)->unique()->values())
            ->orderBy('id')
            ->get()
            ->groupBy($familyOf);

        $palette = ProductColours::palette();
        $products->each(function (Product $product) use ($members, $familyOf, $palette): void {
            $family = $members->get($familyOf($product), collect([$product]));
            $prices = $family->map(fn (Product $member) => $member->onlinePrice());
            $colours = $family->map(fn (Product $member) => trim((string) $member->color))->filter()->unique()->values();

            $product->setAttribute('family_summary', [
                'id' => $familyOf($product),
                'count' => $family->count(),
                'designs' => $family->map(fn (Product $member) => trim((string) $member->variant_value))->filter()->unique()->values()->all(),
                'sizes' => $family->map(fn (Product $member) => trim((string) $member->size))->filter()->unique()->values()->all(),
                'colours' => $colours->map(fn (string $colour) => [
                    'value' => $colour,
                    'name' => ProductColours::displayName($colour),
                    'hex' => ProductColours::hexFor($colour, $palette),
                ])->all(),
                'min_price' => $prices->min(),
                'max_price' => $prices->max(),
                'stock_total' => $family->sum(fn (Product $member) => (float) $member->stock_total),
            ]);
        });
    }

    /**
     * Apply filters to the query.
     *
     * @param  Builder<Product>  $query
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            // Direct lookups — a scan or a scanner tapping the list endpoint
            // wants one row, not the whole catalog filtered by nothing.
            ->when($filters['product_id'] ?? null, function ($q, $value) {
                return $q->whereKey($value);
            })
            // Every size and colour of one family (its head and the variants under it).
            ->when($filters['family_id'] ?? null, function ($q, $value) {
                return $q->where(fn ($family) => $family->whereKey($value)->orWhere('parent_id', $value));
            })
            ->when($filters['barcode'] ?? null, function ($q, $value) {
                return $q->where('barcode', $value);
            })
            // Category filters
            ->when($filters['main_category_id'] ?? null, function ($q, $value) {
                return $q->where('main_category_id', $value);
            })
            ->when($filters['type'] ?? null, function ($q, $value) {
                return $q->where('type', $value);
            })
            ->when($filters['sub_category_id'] ?? null, function ($q, $value) {
                return $q->where('sub_category_id', $value);
            })
            // Brand filter
            ->when(isset($filters['brand_id']), function ($q) use ($filters) {
                return $q->where('brand_id', $filters['brand_id']);
            })
            // Storefront callers only — the POS catalog must still see hidden brands/categories.
            ->when($filters['online_only'] ?? false, function ($q) {
                return $q->onlineVisible();
            })
            // HSN code filter (used to surface related products)
            ->when($filters['hsn_code'] ?? null, function ($q, $value) {
                return $q->where('hsn_code', $value);
            })
            // Inventory branch filter
            ->when($filters['branch_id'] ?? null, function ($q, $value) {
                return $q->whereHas('inventories', function ($invQ) use ($value) {
                    $invQ->where('branch_id', $value);
                });
            })
            // In stock only filter — scoped to the selected branch so it matches
            // the branch-specific "Out of stock" badge shown on each card.
            ->when(($filters['in_stock_only'] ?? false) && ($filters['type'] ?? null) === 'product', function ($q) use ($filters) {
                return $q->whereHas('inventories', function ($invQ) use ($filters) {
                    $invQ->where('quantity', '>', 0)
                        ->when($filters['branch_id'] ?? null, function ($branchQ, $branchId) {
                            return $branchQ->where('branch_id', $branchId);
                        });
                });
            })
            // Only what has a spin behind it.
            //
            // Two frames, not one: a single angle image is a leftover upload,
            // not a sequence anything can be spun through — and the clients
            // hide the 360° affordance for it, so a row returned here on the
            // strength of one frame would come back looking like every other.
            ->when($filters['has_360'] ?? false, function ($q) {
                return $q->whereHas('images', function ($imageQ) {
                    $imageQ->where('method', 'angle');
                }, '>=', 2);
            })
            // Size filter — one size, or any of several.
            ->when(SizeFilter::from($filters['size'] ?? null, $filters['sizes'] ?? null), function ($q, $sizes) {
                return $q->whereIn('size', $sizes);
            })
            // Color filter
            ->when($filters['color'] ?? null, function ($q, $value) {
                return $q->where('color', 'like', "%{$value}%");
            })
            // Price range filters
            ->when($filters['min_price'] ?? null, function ($q, $value) {
                return $q->where('mrp', '>=', $value);
            })
            ->when($filters['max_price'] ?? null, function ($q, $value) {
                return $q->where('mrp', '<=', $value);
            })
            // General search filter
            ->when($filters['search'] ?? null, function ($q, $value) use ($filters) {
                $searchDescription = $filters['search_in_description'] ?? false;

                return $q->where(function ($subQuery) use ($value, $searchDescription) {
                    // A scan pastes the whole barcode/code into the search box.
                    // Match it exactly first so the common case is an index hit
                    // rather than a pile of leading-wildcard LIKEs.
                    $subQuery->where('barcode', $value)
                        ->orWhere('code', $value)
                        ->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhere('color', 'like', "%{$value}%")
                        ->orWhere('model', 'like', "%{$value}%");

                    // `description` is TEXT, and including it forces the whole
                    // row to be read for every product in the catalog — roughly
                    // tripling the cost of a search-as-you-type keystroke. Off
                    // by default; callers that want it can ask.
                    if ($searchDescription) {
                        $subQuery->orWhere('description', 'like', "%{$value}%");
                    }
                });
            });
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        // The New Sale order (see appProductSort()): priority in the asked
        // direction, ties broken by name.
        if ($sortBy === 'priority') {
            $query->orderBy('priority', $sortDirection)->orderBy('name');

            return;
        }

        // Map sort fields to actual database columns
        $sortFieldMap = [
            'name' => 'name',
            'price' => 'mrp',
            'mrp' => 'mrp',
            'cost' => 'cost',
        ];

        $sortField = $sortFieldMap[$sortBy] ?? 'name';
        $query->orderBy($sortField, $sortDirection);

        // Add secondary sort for consistency
        if ($sortField !== 'name') {
            $query->orderBy('name', 'asc');
        }
    }
}
