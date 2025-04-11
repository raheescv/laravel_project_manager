<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SaleReturnItem extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'inventory_id',
        'product_id',
        'unit_price',
        'quantity',

        'discount',

        'tax',

        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'sale_return_id' => ['required'],
            'inventory_id' => ['required'],
            'product_id' => ['required'],
            'unit_price' => ['required'],
            'quantity' => ['required'],
            'created_by' => ['required'],
            'updated_by' => ['required'],
        ], $merge);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function getNameAttribute()
    {
        return $this->product?->name;
    }

    public function getEffectiveTotalAttribute()
    {
        if ($this->saleReturn?->other_discount) {
            $discount_percentage = ($this->saleReturn->other_discount / $this->saleReturn->total) * 100;

            return round($this->total - ($discount_percentage * $this->total) / 100, 3);
        } else {
            return $this->total;
        }
    }
}
