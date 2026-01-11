<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class StockCheckItem extends Model
{
    protected $fillable = [
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

    public function stockCheck()
    {
        return $this->belongsTo(StockCheck::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product()
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
