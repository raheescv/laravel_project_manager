<?php

namespace App\Support\Storefront;

use App\Actions\V1\Storefront\StartCheckoutAction;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * A storefront bag priced from the catalogue, ready to quote promo codes against.
 * The one place the worth of a code is decided, for the search and the checkout alike.
 */
final class CouponBag
{
    /**
     * @param  array<int, float>  $totals  product id => line total
     * @param  Collection<int, Product>  $products  keyed by id
     */
    private function __construct(
        private readonly array $totals,
        private readonly Collection $products,
    ) {}

    /**
     * From the browser's product ids and quantities, before a shop is chosen.
     *
     * @param  array<int, array{productId: int, quantity: int}>  $items
     */
    public static function fromItems(array $items): self
    {
        $quantities = [];
        foreach ($items as $item) {
            $productId = (int) $item['productId'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        $products = self::products(array_keys($quantities));
        $totals = $products->mapWithKeys(fn (Product $product): array => [$product->id => StartCheckoutAction::lineTotal($product, $quantities[$product->id])])->all();

        return new self($totals, $products);
    }

    /**
     * From lines the checkout has already priced.
     *
     * @param  array<int, array{product_id: int, total: float}>  $lines
     */
    public static function fromPricedLines(array $lines): self
    {
        $totals = array_column($lines, 'total', 'product_id');

        return new self($totals, self::products(array_keys($totals)));
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Product>
     */
    private static function products(array $ids): Collection
    {
        return Product::query()
            ->whereIn('id', $ids)
            ->where('type', 'product')
            ->get(['id', 'mrp', 'tax', 'brand_id', 'main_category_id', 'sub_category_id'])
            ->keyBy('id');
    }

    public function total(): float
    {
        return round(array_sum($this->totals), 2);
    }

    /** [$mobile] is known only at checkout, so per-customer limits apply there. */
    public function quote(Coupon $coupon, ?string $mobile = null): CouponQuote
    {
        $blocked = match ($coupon->state()) {
            Coupon::STATE_ACTIVE => null,
            Coupon::STATE_EXPIRED => CouponQuote::EXPIRED,
            Coupon::STATE_USED_UP => CouponQuote::USED_UP,
            default => CouponQuote::UNAVAILABLE,
        };

        if ($blocked) {
            return new CouponQuote($blocked);
        }

        $eligible = $this->eligibleTotal($coupon);

        if ($eligible <= 0) {
            return new CouponQuote(CouponQuote::INELIGIBLE);
        }

        if ($mobile && $coupon->per_customer_limit && $coupon->redemptions()->where('customer_mobile', $mobile)->count() >= $coupon->per_customer_limit) {
            return new CouponQuote(CouponQuote::ALREADY_USED);
        }

        $minimum = (float) $coupon->min_bag_total;

        if ($eligible < $minimum) {
            return new CouponQuote(CouponQuote::LOCKED, short: round($minimum - $eligible, 2));
        }

        return $this->priced($coupon, $eligible);
    }

    /**
     * The saving comes off the payable amount and takes the store's round off with
     * it: the sale rounds its own total, and the amount Tap charges has to match it
     * to the cent or the paid order would need a person to review it.
     */
    private function priced(Coupon $coupon, float $eligible): CouponQuote
    {
        $value = (float) $coupon->discount_value;
        $saving = $coupon->discount_type === Coupon::TYPE_PERCENT ? $eligible * $value / 100 : $value;

        if ($coupon->max_discount) {
            $saving = min($saving, (float) $coupon->max_discount);
        }

        $total = $this->total();
        $payable = round($total - min($saving, $eligible), 2);
        $payable = round($payable + saleRoundOffFor($payable), 2);

        // Tap cannot charge nothing, so a code may not swallow the whole bag.
        if ($payable <= 0) {
            return new CouponQuote(CouponQuote::LOCKED, short: round(max($value - $total, 0) + 1, 2));
        }

        return new CouponQuote(CouponQuote::READY, saving: max(round($total - $payable, 2), 0));
    }

    private function eligibleTotal(Coupon $coupon): float
    {
        $covered = array_filter(
            $this->totals,
            fn (int $productId): bool => $this->covers($coupon, $this->products->get($productId)),
            ARRAY_FILTER_USE_KEY,
        );

        return round(array_sum($covered), 2);
    }

    private function covers(Coupon $coupon, ?Product $product): bool
    {
        if (! $product) {
            return false;
        }

        if (in_array((int) $product->id, $coupon->excluded_product_ids ?? [], true)) {
            return false;
        }

        $ids = $coupon->applies_to_ids ?? [];

        return match ($coupon->applies_to) {
            Coupon::SCOPE_BRAND => in_array((int) $product->brand_id, $ids, true),
            Coupon::SCOPE_CATEGORY => array_intersect([(int) $product->main_category_id, (int) $product->sub_category_id], $ids) !== [],
            Coupon::SCOPE_PRODUCT => in_array((int) $product->id, $ids, true),
            default => true,
        };
    }
}
