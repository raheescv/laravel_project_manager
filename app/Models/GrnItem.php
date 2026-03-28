<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnItem extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'grn_id',
        'local_purchase_order_item_id',
        'product_id',
        'quantity',
        'rate',
        // 'total',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function localPurchaseOrderItem()
    {
        return $this->belongsTo(LocalPurchaseOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
