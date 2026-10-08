<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleComboOffer extends Model
{
    protected $fillable = [
        'sale_id',
        'combo_offer_id',
        'amount',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'sale_id' => ['required'],
            'combo_offer_id' => ['required'],
            'amount' => ['required', 'numeric'],
        ], $merge);
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<ComboOffer, $this> */
    public function comboOffer(): BelongsTo
    {
        return $this->belongsTo(ComboOffer::class);
    }

    public static function addComboOfferId($sale_id, $inventory_id, $employee_id, $sale_combo_offer_id)
    {
        SaleItem::where('sale_id', $sale_id)
            ->where('inventory_id', $inventory_id)
            ->where('employee_id', $employee_id)
            ->update(['sale_combo_offer_id' => $sale_combo_offer_id]);
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_combo_offer_id');
    }
}
