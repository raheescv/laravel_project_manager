<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PurchaseReturnItem extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'unit_id',
        'unit_price',
        'quantity',
        'conversion_factor',
        'discount',
        'tax',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'purchase_return_id' => ['required'],
            'product_id' => ['required'],
            'unit_price' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric'],
            'created_by' => ['required'],
            'updated_by' => ['required'],
        ], $merge);
    }

    /** @return BelongsTo<PurchaseReturn, $this> */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    /** @return BelongsTo<PurchaseItem, $this> */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getNameAttribute()
    {
        return $this->product?->name;
    }

    public function getUnitNameAttribute()
    {
        return $this->unit?->name;
    }
}
