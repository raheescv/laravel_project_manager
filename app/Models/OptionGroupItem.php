<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One choice of an option group: its extra price, the stock item it uses per
 * sale ("Red Bull 250ml" × 1) and the group asked next when it is picked.
 */
class OptionGroupItem extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'option_group_id',
        'name',
        'price',
        'product_id',
        'quantity',
        'next_group_id',
        'is_default',
        'sort',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'quantity' => 'float',
            'is_default' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'product_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'next_group_id' => ['nullable', 'integer'],
        ];
    }

    /** @return BelongsTo<OptionGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<OptionGroup, $this> */
    public function nextGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'next_group_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
