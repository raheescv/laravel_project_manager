<?php

namespace App\Actions\V1\Storefront;

use App\Models\Coupon;
use App\Support\Storefront\CouponBag;
use App\Support\Storefront\CouponQuote;

class SearchCouponsAction
{
    /** Most results one search returns. */
    public const LIMIT = 20;

    /** The order results are shown in: what can be used first. */
    private const STATE_ORDER = [CouponQuote::READY, CouponQuote::LOCKED, CouponQuote::INELIGIBLE, CouponQuote::ALREADY_USED, CouponQuote::USED_UP, CouponQuote::EXPIRED];

    /**
     * Find the codes a customer's search reaches and quote each against their bag.
     * Nothing is listed until they search: a code that ended or ran out only comes
     * back when typed in full, so they learn why it failed.
     *
     * @param  array<int, array{productId: int, quantity: int}>  $items
     * @return array{total: float, results: list<array<string, mixed>>}
     */
    public function execute(string $search, array $items): array
    {
        $bag = CouponBag::fromItems($items);

        $results = Coupon::query()
            ->withCount('redemptions')
            ->findableBy($search)
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Coupon $coupon): array => ['coupon' => $coupon, 'quote' => $bag->quote($coupon)])
            ->reject(fn (array $row): bool => $row['coupon']->code !== $search && in_array($row['quote']->state, [CouponQuote::EXPIRED, CouponQuote::USED_UP], true))
            ->sortBy([
                fn (array $a, array $b): int => array_search($a['quote']->state, self::STATE_ORDER, true) <=> array_search($b['quote']->state, self::STATE_ORDER, true),
                fn (array $a, array $b): int => $b['quote']->saving <=> $a['quote']->saving,
                fn (array $a, array $b): int => $a['quote']->short <=> $b['quote']->short,
            ])
            ->map(fn (array $row): array => $this->row($row['coupon'], $row['quote'], $bag->total()))
            ->values()
            ->all();

        return ['total' => $bag->total(), 'results' => $results];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Coupon $coupon, CouponQuote $quote, float $total): array
    {
        return [
            'code' => $coupon->code,
            'title' => $coupon->title,
            'title_arabic' => $coupon->title_arabic,
            'state' => $quote->state,
            'saving' => $quote->saving,
            'percent' => $this->percent($coupon, $quote),
            'short' => $quote->short,
            'new_total' => round($total - $quote->saving, 2),
            'is_private' => ! $coupon->is_searchable,
        ];
    }

    /**
     * The percent to show beside the saving. Hidden when the maximum discount cut
     * the saving, since it is then no longer that percent; the 1 allows for the
     * store's round off moving the saving just below the maximum.
     */
    private function percent(Coupon $coupon, CouponQuote $quote): ?float
    {
        if ($coupon->discount_type !== Coupon::TYPE_PERCENT) {
            return null;
        }

        $isCapped = $coupon->max_discount !== null && $quote->saving >= (float) $coupon->max_discount - 1;

        return $isCapped ? null : (float) $coupon->discount_value;
    }
}
