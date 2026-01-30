<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sub_unit_id',
        'conversion_factor',
        'barcode',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'product_id' => ['required'],
            'sub_unit_id' => ['required'],
            'conversion_factor' => ['required'],
            'barcode' => ['required'],
        ], $merge);
    }

    public function subUnit()
    {
        return $this->belongsTo(Unit::class, 'sub_unit_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
