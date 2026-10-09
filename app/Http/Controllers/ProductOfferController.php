<?php

namespace App\Http\Controllers;

use App\Actions\Product\Offer\CreateAction;
use App\Actions\Product\Offer\DeleteAction;
use App\Actions\Product\Offer\UpdateAction;
use App\Http\Requests\Product\ProductOfferRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductOfferController extends Controller
{
    use ApiResponseTrait;

    /** Products sent when a whole category is opened or added at once. */
    public const CATEGORY_LIMIT = 1000;

    /** Products sent for a free-text search. */
    public const SEARCH_LIMIT = 40;

    public function index(Request $request): View
    {
        return view('product.offer.index', ['type' => $this->type($request), 'permissions' => $this->permissions()]);
    }

    public function page(Request $request, ?int $id = null): View
    {
        $type = $id ? ProductOffer::query()->whereKey($id)->value('type') : $this->type($request);
        abort_unless($type, 404);

        return view('product.offer.page', ['id' => $id, 'type' => $type, 'permissions' => $this->permissions()]);
    }

    /**
     * Which catalogue the request is about: `?type=service` from the Service
     * page, products otherwise.
     */
    private function type(Request $request): string
    {
        $type = (string) $request->input('type');

        return array_key_exists($type, ProductOffer::types()) ? $type : 'product';
    }

    /**
     * @return array<string, bool>
     */
    private function permissions(): array
    {
        $user = Auth::user();

        return collect(['create', 'edit', 'delete'])
            ->mapWithKeys(fn (string $action): array => [$action => $user->can("product offer.{$action}")])
            ->all();
    }

    public function list(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $today = now()->toDateString();

        $offers = ProductOffer::query()
            ->where('type', $this->type($request))
            ->with('creator:id,name')
            ->withCount('prices')
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->input('state'), function (Builder $query, string $state) use ($today): void {
                match ($state) {
                    'running' => $query->where('status', 'active')->where('start_date', '<=', $today)->where('end_date', '>=', $today),
                    'scheduled' => $query->where('status', 'active')->where('start_date', '>', $today),
                    'expired' => $query->where('end_date', '<', $today),
                    'disabled' => $query->where('status', 'disabled'),
                    default => null,
                };
            })
            ->orderByDesc('id')
            ->paginate(25);

        return $this->sendSuccess([
            'offers' => $offers->getCollection()->map(fn (ProductOffer $offer): array => [
                'id' => $offer->id,
                'name' => $offer->name,
                'start_date' => $offer->start_date->toDateString(),
                'end_date' => $offer->end_date->toDateString(),
                'status' => $offer->status,
                'state' => $this->state($offer, $today),
                'products_count' => $offer->prices_count,
                'created_by' => $offer->creator?->name,
            ])->values(),
            'current_page' => $offers->currentPage(),
            'last_page' => $offers->lastPage(),
            'total' => $offers->total(),
        ], 'Offers loaded.');
    }

    /**
     * Where the offer is in its life: running today, scheduled, expired or switched off.
     */
    private function state(ProductOffer $offer, string $today): string
    {
        return match (true) {
            $offer->status === 'disabled' => 'disabled',
            $offer->end_date->toDateString() < $today => 'expired',
            $offer->start_date->toDateString() > $today => 'scheduled',
            default => 'running',
        };
    }

    public function show(int $id): JsonResponse
    {
        $offer = ProductOffer::find($id);
        if (! $offer) {
            return $this->sendNotFoundError('Offer not found.');
        }

        $prices = $offer->prices()->pluck('amount', 'product_id');
        $products = $this->catalog()->whereIn('products.id', $prices->keys())->get();

        return $this->sendSuccess([
            'id' => $offer->id,
            'type' => $offer->type,
            'name' => $offer->name,
            'start_date' => $offer->start_date->toDateString(),
            'end_date' => $offer->end_date->toDateString(),
            'status' => $offer->status,
            'items' => $products->map(fn (Product $product): array => $this->productRow($product) + ['amount' => (float) $prices[$product->id]])->values(),
        ], 'Offer loaded.');
    }

    /**
     * Main categories with their sub categories and how many selling products
     * (or services, for `?type=service`) each holds — the editor's left rail.
     */
    public function categories(Request $request): JsonResponse
    {
        $counts = Product::query()->where('type', $this->type($request))->isSelling()
            ->toBase()
            ->selectRaw('main_category_id, sub_category_id, COUNT(*) as aggregate')
            ->groupBy('main_category_id', 'sub_category_id')
            ->get();

        $names = Category::query()
            ->whereIn('id', $counts->pluck('main_category_id')->merge($counts->pluck('sub_category_id'))->filter()->unique())
            ->pluck('name', 'id');

        $tree = $counts->groupBy(fn ($row): int => (int) $row->main_category_id)
            ->map(fn ($rows, int $mainId): array => [
                'id' => $mainId,
                'name' => $names[$mainId] ?? 'Uncategorised',
                'count' => (int) $rows->sum('aggregate'),
                'subs' => $rows->filter(fn ($row): bool => (bool) $row->sub_category_id)
                    ->map(fn ($row): array => [
                        'id' => (int) $row->sub_category_id,
                        'name' => $names[$row->sub_category_id] ?? 'Unknown',
                        'count' => (int) $row->aggregate,
                    ])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values(),
            ])
            ->sortBy(fn (array $main): string => ($main['id'] === 0 ? '~' : '').strtolower($main['name']))
            ->values();

        return $this->sendSuccess($tree, 'Categories loaded.');
    }

    /**
     * Selling products of the requested `type` for a category (`main_category_id`, 0 = uncategorised,
     * optionally narrowed by `sub_category_id`) or a free-text `search`.
     */
    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $hasCategory = $request->filled('main_category_id');

        $query = $this->catalog()->where('products.type', $this->type($request))->where('products.is_selling', true)
            ->when($hasCategory, function (Builder $query) use ($request): void {
                $mainId = (int) $request->input('main_category_id');
                $mainId === 0 ? $query->whereNull('products.main_category_id') : $query->where('products.main_category_id', $mainId);
            })
            ->when($request->filled('sub_category_id'), fn (Builder $query) => $query->where('products.sub_category_id', (int) $request->input('sub_category_id')))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.code', 'like', "{$search}%")
                    ->orWhere('products.barcode', $search);
            }))
            ->orderBy('products.name');

        $limit = $hasCategory ? self::CATEGORY_LIMIT : self::SEARCH_LIMIT;
        $products = $query->limit($limit + 1)->get();

        return $this->sendSuccess([
            'products' => $products->take($limit)->map(fn (Product $product): array => $this->productRow($product))->values(),
            'truncated' => $products->count() > $limit,
        ], 'Products loaded.');
    }

    /**
     * @return Builder<Product>
     */
    private function catalog(): Builder
    {
        return Product::query()
            ->select(['id', 'code', 'name', 'thumbnail', 'mrp', 'cost', 'brand_id', 'main_category_id', 'sub_category_id'])
            ->with(['brand:id,name', 'mainCategory:id,name', 'subCategory:id,name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function productRow(Product $product): array
    {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'thumbnail' => $product->thumbnail,
            'brand' => $product->brand?->name,
            'mrp' => (float) $product->mrp,
            'cost' => (float) $product->cost,
            'main_category_id' => (int) $product->main_category_id,
            'main_category' => $product->mainCategory->name ?? 'Uncategorised',
            'sub_category_id' => $product->sub_category_id ? (int) $product->sub_category_id : null,
            'sub_category' => $product->subCategory?->name,
        ];
    }

    public function store(ProductOfferRequest $request, CreateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->validated(), Auth::id()), 201);
    }

    public function update(int $id, ProductOfferRequest $request, UpdateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->validated(), $id, Auth::id()));
    }

    public function destroy(int $id, DeleteAction $action): JsonResponse
    {
        return $this->respond($action->execute($id));
    }

    /**
     * @param  array{success: bool, message: string, data?: mixed}  $response
     */
    private function respond(array $response, int $code = 200): JsonResponse
    {
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        return $this->sendSuccess(['id' => $response['data']->id], $response['message'], $code);
    }
}
