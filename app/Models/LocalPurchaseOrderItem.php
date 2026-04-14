<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalPurchaseOrderItem extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'local_purchase_order_id',
        'product_id',
        'quantity',
        'rate',
        // 'total',
    ];

    public function order()
    {
        return $this->belongsTo(LocalPurchaseOrder::class, 'local_purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
