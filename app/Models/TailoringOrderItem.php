<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
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
        'quantity_per_item',
        'unit_price',
        'stitch_rate',

        'discount',

        'tax',

        'tailor_id',
        'tailor_commission',

        'used_quantity',
        'wastage',

        'item_completion_date',
        'completed_quantity',
        // 'status',
        'is_selected_for_completion',
        'tailoring_notes',
        'rating',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_per_item' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'stitch_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'tailor_commission' => 'decimal:2',
        'used_quantity' => 'decimal:3',
        'wastage' => 'decimal:3',
        'item_completion_date' => 'date:Y-m-d',
        'completed_quantity' => 'decimal:3',
        'is_selected_for_completion' => 'boolean',
        'rating' => 'integer',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'tailoring_order_id' => ['required'],
            'product_name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'quantity_per_item' => ['nullable', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ], $merge);
    }

    protected static function booted()
    {
        static::creating(function ($item): void {
            if (empty($item->created_by)) {
                $item->created_by = Auth::id();
            }
            if (empty($item->updated_by)) {
                $item->updated_by = Auth::id();
            }
        });

        static::updating(function ($item): void {
            if (empty($item->updated_by)) {
                $item->updated_by = Auth::id();
            }
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

    /**
     * Persist the item. gross_amount, net_amount, tax_amount, total are computed by the database (storedAs).
     */
    public function calculateTotal(): void
    {
        $this->save();
        $this->refresh();
    }

    public function calculateTailorCommission()
    {
        $this->save();
    }

    public function updateCompletion($data)
    {
        $this->fill($data);
        if (isset($data['rating'])) {
            $this->rating = max(1, min(5, (int) $data['rating']));
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
