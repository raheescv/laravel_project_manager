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

    /** What the "available codes" list shows: codes this bag can use now, or with more in it. */
    private const LISTED_STATES = [CouponQuote::READY, CouponQuote::LOCKED];

    /**
     * Find the codes a customer's search reaches and quote each against their bag.
     * A code that ended or ran out only comes back when typed in full, so they
     * learn why it failed.
     *
     * With no search, the customer asked to see what is on offer: open, searchable
     * codes their bag can use now or once it holds more. Private codes never show.
     *
     * @param  array<int, array{productId: int, quantity: int}>  $items
     * @return array{total: float, results: list<array<string, mixed>>}
     */
    public function execute(?string $search, array $items): array
    {
        $bag = CouponBag::fromItems($items);

        $results = Coupon::query()
            ->withCount('redemptions')
            ->when($search, fn ($query, string $search) => $query->findableBy($search), fn ($query) => $query->listable())
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Coupon $coupon): array => ['coupon' => $coupon, 'quote' => $bag->quote($coupon)])
            ->reject(fn (array $row): bool => $search
                ? $row['coupon']->code !== $search && in_array($row['quote']->state, [CouponQuote::EXPIRED, CouponQuote::USED_UP], true)
                : ! in_array($row['quote']->state, self::LISTED_STATES, true))
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
            'percent' => $coupon->percentFor($quote->saving),
            'short' => $quote->short,
            'new_total' => round($total - $quote->saving, 2),
            'is_private' => ! $coupon->is_searchable,
        ];
    }
}
