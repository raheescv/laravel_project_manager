<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePackage extends Model
{
    protected $fillable = [
        'sale_id',
        'service_package_id',
        'amount',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'sale_id' => ['required'],
            'service_package_id' => ['required'],
            'amount' => ['required', 'numeric'],
        ], $merge);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public static function addPackageId($sale_id, $inventory_id, $employee_id, $sale_package_id)
    {
        SaleItem::where('sale_id', $sale_id)
            ->where('inventory_id', $inventory_id)
            ->where('employee_id', $employee_id)
            ->update(['sale_package_id' => $sale_package_id]);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_package_id');
    }
}
