<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SaleItem extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'employee_id',
        'inventory_id',
        'product_id',
        'unit_price',
        'quantity',
        'discount',
        'tax',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'sale_id' => ['required'],
            'employee_id' => ['required'],
            'inventory_id' => ['required'],
            'product_id' => ['required'],
            'unit_price' => ['required'],
            'quantity' => ['required'],
            'created_by' => ['required'],
            'updated_by' => ['required'],
        ], $merge);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function getNameAttribute()
    {
        return $this->product?->name;
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee?->name;
    }
}
