<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SaleItem extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'employee_id',
        'assistant_id',
        'inventory_id',
        'product_id',
        'unit_id',
        'sale_combo_offer_id',
        'unit_price',
        'quantity',
        'conversion_factor',

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

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    /** @return BelongsTo<SaleComboOffer, $this> */
    public function salePackage(): BelongsTo
    {
        return $this->belongsTo(SaleComboOffer::class, 'sale_combo_offer_id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getNameAttribute()
    {
        return $this->product?->name;
    }

    public function getUnitNameAttribute()
    {
        return $this->unit?->name;
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee?->name;
    }

    public function getAssistantNameAttribute()
    {
        return $this->assistant?->name;
    }

    public function getEffectiveTotalAttribute()
    {
        if ($this->sale?->other_discount != 0 && $this->sale->total != 0) {
            $discount_percentage = ($this->sale->other_discount / $this->sale->total) * 100;

            return round($this->total - ($discount_percentage * $this->total) / 100, 3);
        } else {
            return $this->total;
        }
    }
}
