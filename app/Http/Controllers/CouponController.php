<?php

namespace App\Http\Controllers;

use App\Actions\Coupon\CreateAction;
use App\Actions\Coupon\DeleteAction;
use App\Actions\Coupon\ToggleAction;
use App\Actions\Coupon\UpdateAction;
use App\Http\Requests\Sale\CouponRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\StorefrontCheckout;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CouponController extends Controller
{
    use ApiResponseTrait;

    /** Where each "applies to" choice reads its names from. */
    private const SCOPE_MODELS = [
        Coupon::SCOPE_BRAND => Brand::class,
        Coupon::SCOPE_CATEGORY => Category::class,
        Coupon::SCOPE_PRODUCT => Product::class,
    ];

    public function index(): View
    {
        $user = Auth::user();

        return view('sale.coupon.index', [
            'permissions' => collect(['create', 'edit', 'delete'])
                ->mapWithKeys(fn (string $action): array => [$action => $user->can("coupon.{$action}")])
                ->all(),
            'currency' => tenant_cache('base_currency_code') ?: tenant_cache('currency_code'),
            'lookups' => [
                Coupon::SCOPE_BRAND => route('settings::brand::list'),
                Coupon::SCOPE_CATEGORY => route('settings::category::list'),
                Coupon::SCOPE_PRODUCT => route('product::list', ['type' => 'product']),
            ],
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $state = in_array($request->input('state'), Coupon::STATES, true) ? $request->input('state') : null;

        $coupons = Coupon::query()
            ->withCount('redemptions')
            ->withSum('redemptions', 'coupon_discount')
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"),
            ))
            ->when($state, fn (Builder $query, string $state) => $query->inState($state))
            ->orderByDesc('id')
            ->paginate(25);

        $names = $this->scopeNames($coupons->getCollection());

        return $this->sendSuccess([
            'coupons' => $coupons->getCollection()->map(fn (Coupon $coupon): array => $this->row($coupon, $names))->values(),
            'current_page' => $coupons->currentPage(),
            'last_page' => $coupons->lastPage(),
            'total' => $coupons->total(),
            'summary' => $this->summary(),
        ], 'Coupons loaded.');
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     * @return array<string, \Illuminate\Support\Collection<int, string>>
     */
    private function scopeNames(Collection $coupons): array
    {
        $names = [];

        foreach (self::SCOPE_MODELS as $scope => $model) {
            $ids = $coupons->where('applies_to', $scope)->pluck('applies_to_ids')
                ->when($scope === Coupon::SCOPE_PRODUCT, fn ($ids) => $ids->merge($coupons->pluck('excluded_product_ids')))
                ->flatten()
                ->filter()
                ->unique();
            $names[$scope] = $ids->isEmpty() ? collect() : $model::query()->whereIn('id', $ids)->pluck('name', 'id');
        }

        return $names;
    }

    /**
     * @param  array<string, \Illuminate\Support\Collection<int, string>>  $names
     * @return array<string, mixed>
     */
    private function row(Coupon $coupon, array $names): array
    {
        return [
            ...$coupon->only(['id', 'code', 'title', 'title_arabic', 'discount_type', 'applies_to', 'usage_limit', 'per_customer_limit', 'is_searchable', 'is_active']),
            'discount_value' => (float) $coupon->discount_value,
            'max_discount' => $coupon->max_discount === null ? null : (float) $coupon->max_discount,
            'min_bag_total' => (float) $coupon->min_bag_total,
            'starts_on' => $coupon->starts_on->toDateString(),
            'ends_on' => $coupon->ends_on?->toDateString(),
            'state' => $coupon->state(),
            'used' => $coupon->usedCount(),
            'discount_given' => (float) $coupon->redemptions_sum_coupon_discount,
            'items' => $this->named($coupon->applies_to_ids, $names[$coupon->applies_to] ?? collect()),
            'excluded_items' => $this->named($coupon->excluded_product_ids, $names[Coupon::SCOPE_PRODUCT]),
        ];
    }

    /**
     * @param  array<int, int>|null  $ids
     * @param  \Illuminate\Support\Collection<int, string>  $names
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string}>
     */
    private function named(?array $ids, \Illuminate\Support\Collection $names): \Illuminate\Support\Collection
    {
        return collect($ids ?? [])->map(fn (int $id): array => ['id' => $id, 'name' => $names[$id] ?? "#{$id}"])->values();
    }

    /**
     * Counts for the filter chips and the figures above the table. They ignore the
     * search and filter, so the page always shows the whole picture.
     *
     * @return array<string, mixed>
     */
    private function summary(): array
    {
        $redemptions = StorefrontCheckout::withoutGlobalScope(AssignedBranchScope::class)
            ->whereNotNull('coupon_id')
            ->whereIn('status', [StorefrontCheckout::STATUS_PAID, StorefrontCheckout::STATUS_REVIEW]);

        return [
            'all' => Coupon::query()->count(),
            'states' => collect(Coupon::STATES)->mapWithKeys(fn (string $state): array => [$state => Coupon::query()->inState($state)->count()]),
            'times_used' => (clone $redemptions)->count(),
            'discount_given' => (float) (clone $redemptions)->sum('coupon_discount'),
        ];
    }

    public function store(CouponRequest $request, CreateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->validated(), Auth::id()), 201);
    }

    public function update(int $id, CouponRequest $request, UpdateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->validated(), $id, Auth::id()));
    }

    public function toggle(int $id, Request $request, ToggleAction $action): JsonResponse
    {
        return $this->respond($action->execute($id, $request->boolean('is_active'), Auth::id()));
    }

    public function destroy(int $id, DeleteAction $action): JsonResponse
    {
        return $this->respond($action->execute($id));
    }

    /**
     * @param  array{success: bool, message: string, data?: Coupon}  $response
     */
    private function respond(array $response, int $code = 200): JsonResponse
    {
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        return $this->sendSuccess(['id' => $response['data']->id], $response['message'], $code);
    }
}
