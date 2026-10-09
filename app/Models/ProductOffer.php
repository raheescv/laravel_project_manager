<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named, dated offer over many products. Each product's offer price is an
 * ordinary `product_prices` row (price_type = offer) pointing back here, so the
 * POS keeps reading it through ProductPrice::offer() unchanged.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $type
 * @property string $name
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property int $created_by
 * @property int $updated_by
 */
class ProductOffer extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * The catalogue an offer prices: the Product page manages product offers,
     * the Service page service offers.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'product' => 'Product',
            'service' => 'Service',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Active',
            'disabled' => 'Inactive',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(int $id = 0): array
    {
        return [
            'type' => ['required', 'in:'.implode(',', array_keys(self::types()))],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(self::statuses()))],
        ];
    }

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_offer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
