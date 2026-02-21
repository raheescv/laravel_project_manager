<?php

namespace App\Models;

use App\Actions\Tailoring\Order\SyncTailorAssignmentAction;
use App\Events\TailoringOrderUpdatedEvent;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'tailoring_category_model_type_id',
        'inventory_id',
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

        'used_quantity',
        'wastage',

        'item_completion_date',
        'completed_quantity',
        // 'pending_quantity',
        'delivered_quantity',
        // 'completion_status',
        // 'delivery_status',
        // 'status',
        'tailoring_notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'inventory_id' => 'integer',
        'quantity' => 'decimal:3',
        'quantity_per_item' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'stitch_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'used_quantity' => 'decimal:3',
        'wastage' => 'decimal:3',
        'item_completion_date' => 'date:Y-m-d',
        'completed_quantity' => 'decimal:3',
        'delivered_quantity' => 'decimal:3',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'tailoring_order_id' => ['required'],
            'inventory_id' => ['nullable', 'exists:inventories,id'],
            'product_name' => ['required'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'quantity_per_item' => ['nullable', 'numeric', 'min:0.001'],
            'unit_price' => ['numeric'],
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

        static::updated(function ($model): void {
            if (! $model->wasChanged('delivered_quantity') && ! $model->wasChanged('completed_quantity')) {
                return;
            }

            $model->loadMissing('order');
            $order = $model->order;

            if (! $order) {
                return;
            }
            if ($model->wasChanged('delivered_quantity') || $model->wasChanged('completed_quantity')) {
                event(new TailoringOrderUpdatedEvent('item_quantity_change', $order));
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

    public function categoryModelType(): BelongsTo
    {
        return $this->belongsTo(TailoringCategoryModelType::class, 'tailoring_category_model_type_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function tailorAssignments(): HasMany
    {
        return $this->hasMany(TailoringOrderItemTailor::class, 'tailoring_order_item_id');
    }

    public function latestTailorAssignment(): HasOne
    {
        return $this->hasOne(TailoringOrderItemTailor::class, 'tailoring_order_item_id')->latestOfMany('id');
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

    public function scopeByModelType($query, $modelTypeId)
    {
        return $query->where('tailoring_category_model_type_id', $modelTypeId);
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
        $exceptData = [
            'tailor_assignment',
            'tailor_assignments',
            'status',
        ];
        $this->fill(collect($data)->except($exceptData)->toArray());
        $this->save();

        (new SyncTailorAssignmentAction())->execute($this, $data);
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

    public function getModelTypeNameAttribute()
    {
        return $this->categoryModelType?->name;
    }
}
