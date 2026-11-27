<?php

namespace App\Support;

use App\Models\Inventory;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The product catalogue behind the purchase "Browse catalogue" drawer: a
 * product-list style search with one filter per column (name, SKU, barcode,
 * colour, size). Grouped, a size / colour family is one entry carrying the
 * members that match; flat, every product is its own row. Stock is the
 * current branch's own stock (not stock held by employees).
 *
 * @phpstan-type CatalogueFilters array{name: string, sku: string, barcode: string, colour: string, size: string}
 * @phpstan-type CatalogueSort array{column: string, direction: 'asc'|'desc'}
 * @phpstan-type CatalogueMember array{id: int, family_id: int, name: string, code: ?string, barcode: ?string, size: ?string, colour: string, colour_hex: ?string, variant: string, cost: float, mrp: float, stock: float, thumbnail: ?string}
 */
final class ProductCatalogue
{
    public const FAMILIES_PER_PAGE = 20;

    public const ROWS_PER_PAGE = 50;

    /** Sortable columns: the request key => the products column it orders by ("stock" is a computed subquery). */
    public const SORTABLE = ['name' => 'products.name', 'code' => 'products.code', 'barcode' => 'products.barcode', 'colour' => 'products.color', 'size' => 'products.size', 'cost' => 'products.cost', 'stock' => 'stock'];

    private const COLUMNS = ['products.id', 'products.parent_id', 'products.name', 'products.code', 'products.barcode', 'products.size', 'products.color', 'products.variant_value', 'products.cost', 'products.mrp', 'products.thumbnail', 'products.brand_id'];

    /** @var array<string, string>|null */
    private ?array $palette = null;

    /**
     * @param  CatalogueFilters  $filters
     * @param  CatalogueSort  $sort
     */
    public function __construct(private readonly array $filters, private readonly array $sort = ['column' => 'name', 'direction' => 'asc']) {}

    /**
     * Family heads (and standalone products) whose family has a member matching
     * every filter, each with those matching members.
     *
     * @return array{data: array<int, array{id: int, name: string, code: ?string, brand: ?string, thumbnail: ?string, is_family: bool, variant_count: int, members: array<int, CatalogueMember>}>, meta: array{current_page: int, last_page: int, total: int}}
     */
    public function families(int $page = 1): array
    {
        $memberFilter = $this->memberConstraint();

        $heads = Product::query()
            ->product()
            ->familyHeads()
            ->select(self::COLUMNS)
            ->with('brand:id,name')
            ->withCount('variants')
            ->when($this->sort['column'] === 'stock', fn (Builder $query) => $query->addSelect(['stock' => $this->stockQuery(family: true)]))
            ->when($this->nameTerms(), fn (Builder $query, array $terms) => $this->matchName($query, $terms))
            ->when($memberFilter !== null, fn (Builder $query) => $query->whereIn('products.id', Product::query()->product()->selectRaw('COALESCE(parent_id, id)')->where($memberFilter)))
            ->tap(fn (Builder $query) => $this->applySort($query))
            ->paginate(self::FAMILIES_PER_PAGE, ['*'], 'page', $page);

        $headIds = $heads->getCollection()->pluck('id')->all();

        $members = $this->membersQuery()
            ->where(fn (Builder $query) => $query->whereIn('products.id', $headIds)->orWhereIn('products.parent_id', $headIds))
            ->when($memberFilter !== null, fn (Builder $query) => $query->where($memberFilter))
            ->orderBy('products.id')
            ->get()
            ->groupBy(fn (Product $product) => $product->familyHeadId());

        $data = $heads->getCollection()->map(fn (Product $head) => [
            'id' => (int) $head->id,
            'name' => (string) $head->name,
            'code' => $head->code,
            'brand' => $head->brand?->name,
            'thumbnail' => $head->thumbnail,
            'is_family' => $head->variants_count > 0,
            'variant_count' => $head->variants_count + 1,
            'members' => $members->get($head->id, collect())->map(fn (Product $member) => $this->member($member))->values()->all(),
        ])->values()->all();

        return ['data' => $data, 'meta' => $this->meta($heads)];
    }

    /**
     * Every matching product (head, variant or standalone) on its own row.
     *
     * @return array{data: array<int, CatalogueMember>, meta: array{current_page: int, last_page: int, total: int}}
     */
    public function rows(int $page = 1): array
    {
        $memberFilter = $this->memberConstraint();

        $rows = $this->membersQuery()
            ->when($this->nameTerms(), fn (Builder $query, array $terms) => $this->matchName($query, $terms))
            ->when($memberFilter !== null, fn (Builder $query) => $query->where($memberFilter))
            ->tap(fn (Builder $query) => $this->applySort($query))
            ->paginate(self::ROWS_PER_PAGE, ['*'], 'page', $page);

        return [
            'data' => $rows->getCollection()->map(fn (Product $product) => $this->member($product))->values()->all(),
            'meta' => $this->meta($rows),
        ];
    }

    /** Purchasable products with their branch stock. */
    private function membersQuery(): Builder
    {
        return Product::query()
            ->product()
            ->select(self::COLUMNS)
            ->addSelect(['stock' => $this->stockQuery()]);
    }

    /**
     * The current branch's own stock of a product, or (for a family head) of every member of its family.
     */
    private function stockQuery(bool $family = false): Builder
    {
        return Inventory::query()
            ->selectRaw('COALESCE(SUM(inventories.quantity), 0)')
            ->when(
                $family,
                fn (Builder $query) => $query
                    ->join('products as family_members', 'family_members.id', '=', 'inventories.product_id')
                    ->whereRaw('COALESCE(family_members.parent_id, family_members.id) = products.id'),
                fn (Builder $query) => $query->whereColumn('inventories.product_id', 'products.id'),
            )
            ->whereNull('inventories.employee_id')
            ->when(session('branch_id'), fn (Builder $query, $branchId) => $query->where('inventories.branch_id', $branchId));
    }

    /**
     * Orders by the chosen column, blanks last for text columns, then by name and id so pages never shuffle.
     */
    private function applySort(Builder $query): void
    {
        $column = self::SORTABLE[$this->sort['column']] ?? self::SORTABLE['name'];
        $direction = $this->sort['direction'] === 'desc' ? 'desc' : 'asc';

        if (in_array($column, ['products.code', 'products.barcode', 'products.color', 'products.size'], true)) {
            $query->orderByRaw("COALESCE({$column}, '') = ''");
        }

        $query->orderBy($column, $direction);

        if ($column !== 'products.name') {
            $query->orderBy('products.name');
        }

        $query->orderBy('products.id');
    }

    /** @return array<int, string> */
    private function nameTerms(): array
    {
        return preg_split('/\s+/', $this->filters['name'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Every word must appear in the name (English or Arabic): "4d run" finds "Adidas 4D Run".
     *
     * @param  array<int, string>  $terms
     */
    private function matchName(Builder $query, array $terms): Builder
    {
        foreach ($terms as $term) {
            $query->where(fn (Builder $match) => $match
                ->where('products.name', 'like', "%{$term}%")
                ->orWhere('products.name_arabic', 'like', "%{$term}%"));
        }

        return $query;
    }

    /**
     * The SKU / barcode / colour / size filters, applied to a single product row.
     * A colour name also finds variants whose colour was stored as a picked hex.
     */
    private function memberConstraint(): ?Closure
    {
        ['sku' => $sku, 'barcode' => $barcode, 'colour' => $colour, 'size' => $size] = $this->filters;

        if ($sku === '' && $barcode === '' && $colour === '' && $size === '') {
            return null;
        }

        $hexes = $colour === '' ? [] : array_values(array_filter(
            $this->palette(),
            fn (string $name) => str_contains(mb_strtolower($name), mb_strtolower($colour)),
            ARRAY_FILTER_USE_KEY,
        ));

        return fn (Builder $query) => $query
            ->when($sku !== '', fn (Builder $query) => $query->where('products.code', 'like', "%{$sku}%"))
            ->when($barcode !== '', fn (Builder $query) => $query->where('products.barcode', 'like', "%{$barcode}%"))
            ->when($size !== '', fn (Builder $query) => $query->where('products.size', $size))
            ->when($colour !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->where('products.color', 'like', "%{$colour}%")
                ->when($hexes, fn (Builder $match) => $match->orWhereIn('products.color', $hexes))));
    }

    /** @return CatalogueMember */
    private function member(Product $product): array
    {
        $colour = trim((string) $product->color);

        return [
            'id' => (int) $product->id,
            'family_id' => $product->familyHeadId(),
            'name' => (string) $product->name,
            'code' => $product->code,
            'barcode' => $product->barcode,
            'size' => $product->size,
            'colour' => ProductColours::displayName($colour),
            'colour_hex' => $colour === '' ? null : ProductColours::hexFor($colour, $this->palette()),
            'variant' => $product->variantName(),
            'cost' => (float) $product->cost,
            'mrp' => (float) $product->mrp,
            'stock' => (float) $product->getAttribute('stock'),
            'thumbnail' => $product->thumbnail,
        ];
    }

    /** @return array<string, string> */
    private function palette(): array
    {
        return $this->palette ??= ProductColours::palette();
    }

    /** @return array{current_page: int, last_page: int, total: int} */
    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}
