<?php

namespace App\Models;

use App\Models\Scopes\AssignedBranchScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A promo code for the online store. What a code is worth on a given bag is
 * worked out by App\Support\Storefront\CouponBag.
 *
 * Uses are not stored here: a use is a storefront checkout that paid with the
 * code, so a refunded order gives its use back without any bookkeeping.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $title
 * @property string|null $title_arabic
 * @property string $discount_type
 * @property string $discount_value
 * @property string|null $max_discount
 * @property string $min_bag_total
 * @property string $applies_to
 * @property array<int, int>|null $applies_to_ids
 * @property array<int, int>|null $excluded_product_ids
 * @property \Illuminate\Support\Carbon $starts_on
 * @property \Illuminate\Support\Carbon|null $ends_on
 * @property int|null $usage_limit
 * @property int|null $per_customer_limit
 * @property bool $is_searchable
 * @property bool $is_active
 * @property int $created_by
 * @property int $updated_by
 */
class Coupon extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const SCOPE_ALL = 'all';

    public const SCOPE_BRAND = 'brand';

    public const SCOPE_CATEGORY = 'category';

    public const SCOPE_PRODUCT = 'product';

    public const STATE_ACTIVE = 'active';

    public const STATE_SCHEDULED = 'scheduled';

    public const STATE_PAUSED = 'paused';

    public const STATE_EXPIRED = 'expired';

    public const STATE_USED_UP = 'used_up';

    public const STATES = [self::STATE_ACTIVE, self::STATE_SCHEDULED, self::STATE_PAUSED, self::STATE_EXPIRED, self::STATE_USED_UP];

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'title_arabic',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_bag_total',
        'applies_to',
        'applies_to_ids',
        'excluded_product_ids',
        'starts_on',
        'ends_on',
        'usage_limit',
        'per_customer_limit',
        'is_searchable',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_bag_total' => 'decimal:2',
            'applies_to_ids' => 'array',
            'excluded_product_ids' => 'array',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'usage_limit' => 'integer',
            'per_customer_limit' => 'integer',
            'is_searchable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(int $id = 0): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:24', 'regex:/^[A-Z0-9-]+$/', Rule::unique(self::class, 'code')->where('tenant_id', self::getCurrentTenantId())->ignore($id)],
            'title' => ['required', 'string', 'max:120'],
            'title_arabic' => ['nullable', 'string', 'max:120'],
            'discount_type' => ['required', Rule::in([self::TYPE_PERCENT, self::TYPE_FIXED])],
            // A free order cannot be charged through Tap, so a percent stops short of 100.
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:9999999', Rule::when(fn ($input): bool => $input['discount_type'] === self::TYPE_PERCENT, ['max:99.99'])],
            'max_discount' => ['nullable', 'numeric', 'gt:0', 'max:9999999'],
            'min_bag_total' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'applies_to' => ['required', Rule::in([self::SCOPE_ALL, self::SCOPE_BRAND, self::SCOPE_CATEGORY, self::SCOPE_PRODUCT])],
            'applies_to_ids' => ['exclude_if:applies_to,'.self::SCOPE_ALL, 'required', 'array', 'min:1', 'max:200'],
            'applies_to_ids.*' => ['integer'],
            'excluded_product_ids' => ['nullable', 'array', 'max:200'],
            'excluded_product_ids.*' => ['integer'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'is_searchable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** Codes are stored and matched in capitals with no spaces, however they were typed. */
    public static function normalizeCode(?string $code): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', (string) $code));
    }

    /**
     * The columns to save from validated form data, shared by create and update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function attributesFrom(array $data): array
    {
        $isPercent = $data['discount_type'] === self::TYPE_PERCENT;
        $isScoped = $data['applies_to'] !== self::SCOPE_ALL;

        return [
            'code' => $data['code'],
            'title' => $data['title'],
            'title_arabic' => $data['title_arabic'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount' => $isPercent ? ($data['max_discount'] ?? null) : null,
            'min_bag_total' => $data['min_bag_total'] ?? 0,
            'applies_to' => $data['applies_to'],
            'applies_to_ids' => $isScoped ? array_map('intval', $data['applies_to_ids']) : null,
            // Picking products one by one already says exactly which count.
            'excluded_product_ids' => $data['applies_to'] === self::SCOPE_PRODUCT ? null : (array_map('intval', $data['excluded_product_ids'] ?? []) ?: null),
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'per_customer_limit' => $data['per_customer_limit'] ?? null,
            'is_searchable' => $data['is_searchable'],
            'is_active' => $data['is_active'],
        ];
    }

    /**
     * The checkouts that spent this code. A use counts across every branch, so the
     * signed-in user's branch limits must not hide any.
     *
     * @return HasMany<StorefrontCheckout, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(StorefrontCheckout::class)
            ->withoutGlobalScope(AssignedBranchScope::class)
            ->whereIn('status', [StorefrontCheckout::STATUS_PAID, StorefrontCheckout::STATUS_REVIEW]);
    }

    public function usedCount(): int
    {
        return (int) ($this->redemptions_count ?? $this->redemptions()->count());
    }

    /** Where the coupon is in its life. Mirrors scopeInState() for a single row. */
    public function state(): string
    {
        $today = now()->toDateString();

        return match (true) {
            ! $this->is_active => self::STATE_PAUSED,
            $this->ends_on && $this->ends_on->toDateString() < $today => self::STATE_EXPIRED,
            $this->starts_on->toDateString() > $today => self::STATE_SCHEDULED,
            $this->usage_limit && $this->usedCount() >= $this->usage_limit => self::STATE_USED_UP,
            default => self::STATE_ACTIVE,
        };
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInState(Builder $query, string $state): Builder
    {
        $today = now()->toDateString();
        $limit = new Expression('coupons.usage_limit');
        $running = fn (Builder $query): Builder => $query->where('is_active', true)
            ->where('starts_on', '<=', $today)
            ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $today));

        return match ($state) {
            self::STATE_PAUSED => $query->where('is_active', false),
            self::STATE_SCHEDULED => $query->where('is_active', true)->where('starts_on', '>', $today),
            self::STATE_EXPIRED => $query->where('is_active', true)->where('ends_on', '<', $today),
            self::STATE_USED_UP => $running($query)->whereHas('redemptions', null, '>=', $limit),
            default => $running($query)->where(fn (Builder $query) => $query->whereNull('usage_limit')->orWhereHas('redemptions', null, '<', $limit)),
        };
    }

    /**
     * What a customer's search can reach: the code typed in full, or part of a code
     * that is open to searching. A paused or not-yet-started coupon is never found.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFindableBy(Builder $query, string $search): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_on', '<=', now()->toDateString())
            ->where(fn (Builder $query) => $query->where('code', $search)->orWhere(
                fn (Builder $query) => $query->where('is_searchable', true)->where('code', 'like', "%{$search}%"),
            ));
    }
}
