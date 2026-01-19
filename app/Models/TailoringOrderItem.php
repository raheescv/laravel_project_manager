<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class TailoringOrderItem extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tailoring_order_id',
        'item_no',
        'tailoring_category_id',
        'tailoring_category_model_id',
        'product_id',
        'product_name',
        'product_color',
        'unit_id',
        'quantity',
        'unit_price',
        'stitch_rate',
        'gross_amount',
        'discount',
        'net_amount',
        'tax',
        'tax_amount',
        'total',
        // Measurements moved to TailoringOrderMeasurement
        // 'length', ...
        // Completion fields
        'tailor_id',
        'tailor_commission',
        'tailor_total_commission',
        'used_quantity',
        'wastage',
        'total_quantity_used',
        'item_completion_date',
        'is_selected_for_completion',
        'tailoring_notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'stitch_rate' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'tailor_commission' => 'decimal:2',
        'tailor_total_commission' => 'decimal:2',
        'used_quantity' => 'decimal:3',
        'wastage' => 'decimal:3',
        'total_quantity_used' => 'decimal:3',
        'item_completion_date' => 'date',
        'is_selected_for_completion' => 'boolean',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'tailoring_order_id' => ['required'],
            'product_name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ], $merge);
    }

    protected static function booted()
    {
        static::creating(function ($item): void {
            if (empty($item->created_by)) {
                $item->created_by = auth()->id();
            }
            if (empty($item->updated_by)) {
                $item->updated_by = auth()->id();
            }
            $item->calculateAmount();
        });

        static::updating(function ($item): void {
            if (empty($item->updated_by)) {
                $item->updated_by = auth()->id();
            }
            $item->calculateAmount();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TailoringOrder::class, 'tailoring_order_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TailoringCategory::class, 'tailoring_category_id');
    }

    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(TailoringCategoryModel::class, 'tailoring_category_model_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tailor_id');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('tailoring_category_id', $categoryId);
    }

    public function scopeByModel($query, $modelId)
    {
        return $query->where('tailoring_category_model_id', $modelId);
    }

    public function scopeSelectedForCompletion($query)
    {
        return $query->where('is_selected_for_completion', true);
    }

    // Methods
    public function calculateAmount()
    {
        $this->gross_amount = $this->unit_price * $this->quantity;
        $this->net_amount = $this->gross_amount - $this->discount;
        $this->tax_amount = ($this->net_amount * $this->tax) / 100;
        $this->total = $this->net_amount + $this->tax_amount + $this->stitch_rate;
    }

    public function calculateTotal()
    {
        $this->calculateAmount();
        $this->save();
    }

    public function calculateStockBalance($stockQuantity)
    {
        $this->total_quantity_used = $this->used_quantity + $this->wastage;

        return $stockQuantity - $this->total_quantity_used;
    }

    public function calculateTailorCommission()
    {
        $this->tailor_total_commission = $this->tailor_commission * $this->quantity;
        $this->save();
    }

    public function updateCompletion($data)
    {
        $this->fill($data);
        if (isset($data['tailor_commission'])) {
            $this->calculateTailorCommission();
        }
        $this->save();
    }

    // Accessors
    public function getCategoryNameAttribute()
    {
        return $this->category?->name;
    }

    public function getModelNameAttribute()
    {
        return $this->categoryModel?->name;
    }

    public function getTailorNameAttribute()
    {
        return $this->tailor?->name;
    }
}
