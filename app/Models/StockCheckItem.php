<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class StockCheckItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'stock_check_id',
        'inventory_id',
        'product_id',
        'physical_quantity',
        'recorded_quantity',
        'status',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge(
            [
                'stock_check_id' => ['required', 'exists:stock_checks,id'],
                'inventory_id' => ['required', 'exists:inventories,id'],
                'product_id' => ['required', 'exists:products,id'],
                'physical_quantity' => ['required', 'numeric', 'min:0'],
                'recorded_quantity' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(array_keys(stockCheckItemStatuses()))],
            ],
            $merge
        );
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<StockCheck, $this> */
    public function stockCheck(): BelongsTo
    {
        return $this->belongsTo(StockCheck::class);
    }

    /** @return BelongsTo<Inventory, $this> */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
