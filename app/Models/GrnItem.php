<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnItem extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'grn_id',
        'local_purchase_order_item_id',
        'product_id',
        'account_id',
        'quantity',
        'rate',
        // 'total',
    ];

    /** @return BelongsTo<Grn, $this> */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    /** @return BelongsTo<LocalPurchaseOrderItem, $this> */
    public function localPurchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(LocalPurchaseOrderItem::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
