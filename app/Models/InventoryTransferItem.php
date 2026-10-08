<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class InventoryTransferItem extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'inventory_transfer_id',
        'product_id',
        'inventory_id',
        'quantity',
        'remark',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'inventory_transfer_id' => ['required'],
            'inventory_id' => ['required'],
            'quantity' => ['required'],
        ], $merge);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model): void {
            $model->product_id = $model->inventory?->product_id;
        });
    }

    /** @return BelongsTo<InventoryTransfer, $this> */
    public function inventoryTransfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Inventory, $this> */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function getNameAttribute()
    {
        return $this->product?->name;
    }
}
