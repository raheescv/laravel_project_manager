<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * A reusable set of choices the till asks for one item: "Drink base" (pick
 * exactly 1), "Flavour" (pick exactly 1), "Tea add-ons" (pick up to 3). The
 * rule is min_select / max_select (NULL max = no limit; 0 min = optional).
 */
class OptionGroup extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /** How deep follow-up groups may nest (Red Bull → Sugar choice → …). */
    public const MAX_DEPTH = 3;

    protected $fillable = [
        'tenant_id',
        'name',
        'min_select',
        'max_select',
        'sort',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'min_select' => 'integer',
            'max_select' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @param  array<string, mixed>  $merge
     * @return array<string, mixed>
     */
    public static function rules(int $id = 0, array $merge = []): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:100', Rule::unique(self::class, 'name')->where('tenant_id', self::getCurrentTenantId())->ignore($id)],
            'min_select' => ['required', 'integer', 'min:0', 'max:50'],
            'max_select' => ['nullable', 'integer', 'min:1', 'max:50', 'gte:min_select'],
            'status' => ['required', Rule::in(array_keys(activeOrDisabled()))],
        ], $merge);
    }

    /** @return HasMany<OptionGroupItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OptionGroupItem::class)->orderBy('sort')->orderBy('id');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option_groups')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** "Pick exactly 1", "Pick up to 3", "Optional · any", "Pick 1 to 3". */
    public static function ruleLabel(int $min, ?int $max): string
    {
        return match (true) {
            $max !== null && $min === $max => 'Pick exactly '.$min,
            $min === 0 && $max === null => 'Optional · any',
            $min === 0 => 'Optional · up to '.$max,
            $max === null => 'Pick at least '.$min,
            default => 'Pick '.$min.' to '.$max,
        };
    }
}
