<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyRequestItem extends Model
{
    protected $fillable = [
        'supply_request_id',
        'branch_id',
        'product_id',
        'mode',
        'quantity',
        'unit_price',
        'remarks',
    ];

    public static function modeOptions(): array
    {
        return [
            'New' => 'New',
            'Damaged' => 'Damaged',
        ];
    }

    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
