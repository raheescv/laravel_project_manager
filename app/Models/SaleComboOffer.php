<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function comboOffer()
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

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_combo_offer_id');
    }
}
