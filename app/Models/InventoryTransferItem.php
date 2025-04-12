<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        static::creating(function ($model) {
            $model->product_id = $model->inventory?->product_id;
        });
    }

    public function inventoryTransfer()
    {
        return $this->belongsTo(InventoryTransfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function getNameAttribute()
    {
        return $this->product?->name;
    }
}
