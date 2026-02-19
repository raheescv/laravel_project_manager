<?php

namespace App\Models;

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
        'is_selected_for_completion',
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
        'is_selected_for_completion' => 'boolean',
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
        $exceptData = [
            'tailor_assignment',
            'tailor_assignments',
            'status',
        ];
        $this->fill(collect($data)->except($exceptData)->toArray());
        $this->save();

        $this->syncTailorAssignment($data);
    }

    public function syncTailorAssignment(array $data): void
    {
        $tailorAssignments = collect($data['tailor_assignments'] ?? ($data['tailor_assignment'] ?? []))
            ->when(isset($data['tailor_assignment']) && is_array($data['tailor_assignment']), function ($collection) use ($data) {
                if (isset($data['tailor_assignments']) && is_array($data['tailor_assignments']) && ! empty($data['tailor_assignments'])) {
                    return $collection;
                }

                return collect([$data['tailor_assignment']]);
            })
            ->filter(fn ($assignment) => is_array($assignment))
            ->values();

        $units = $this->assignmentUnitCount($data);

        if ($tailorAssignments->isEmpty()) {
            $tailorAssignments = collect(range(1, $units))->map(function ($i) use ($data) {
                return $i === 1 ? [
                    'completion_date' => $data['item_completion_date'] ?? null,
                    'status' => $data['status'] ?? 'pending',
                ] : [];
            });
        }

        if ($tailorAssignments->count() < $units) {
            $tailorAssignments = $tailorAssignments->merge(
                collect(range(1, $units - $tailorAssignments->count()))->map(fn () => [])
            );
        }

        $tailorAssignments = $tailorAssignments->take($units)->values();

        $keptIds = [];
        foreach ($tailorAssignments as $tailorAssignmentData) {
            $tailorAssignmentData = array_merge([
                'tailor_id' => null,
                'tailor_commission' => 0,
                'completion_date' => null,
                'rating' => null,
                'status' => 'pending',
            ], $tailorAssignmentData);

            $assignment = isset($tailorAssignmentData['id']) ? $this->tailorAssignments()->where('id', $tailorAssignmentData['id'])->first() : null;

            if (! $assignment) {
                $assignment = new TailoringOrderItemTailor();
                $assignment->tenant_id = $this->tenant_id;
                $assignment->tailoring_order_item_id = $this->id;
                $assignment->created_by = Auth::id() ?: $this->updated_by;
            }

            $assignment->tailor_id = $tailorAssignmentData['tailor_id'] ?: null;
            $assignment->tailor_commission = (float) ($tailorAssignmentData['tailor_commission'] ?? 0);
            $assignment->completion_date = $tailorAssignmentData['completion_date'] ?: null;
            $assignment->rating = $tailorAssignmentData['rating'] !== null ? max(1, min(5, (int) $tailorAssignmentData['rating'])) : null;
            $assignment->status = in_array($tailorAssignmentData['status'] ?? null, ['pending', 'completed', 'delivered']) ? $tailorAssignmentData['status'] : 'pending';
            $assignment->updated_by = Auth::id() ?: $this->updated_by;
            $assignment->save();

            $keptIds[] = $assignment->id;
        }

        if (! empty($keptIds)) {
            $this->tailorAssignments()->whereNotIn('id', $keptIds)->delete();
        }

        $savedAssignments = $this->tailorAssignments()->whereIn('id', $keptIds)->orderBy('id')->get();
        $completedUnits = $savedAssignments->filter(fn ($assignment) => in_array($assignment->status, ['completed', 'delivered']))->count();
        $deliveredUnits = $savedAssignments->filter(fn ($assignment) => $assignment->status === 'delivered')->count();
        $totalCommission = $savedAssignments->sum('tailor_commission');

        $itemUpdate = [
            'item_completion_date' => $savedAssignments->last()?->completion_date,
            'completed_quantity' => (float) $completedUnits,
            'delivered_quantity' => (float) $deliveredUnits,
            'tailor_total_commission' => $totalCommission,
        ];
        $this->forceFill($itemUpdate)->save();
    }

    public function getTailorAssignmentAttribute()
    {
        dd(1);
        $assignment = $this->relationLoaded('latestTailorAssignment') ? $this->getRelation('latestTailorAssignment') : $this->latestTailorAssignment()->first();

        if (! $assignment) {
            return [
                'id' => null,
                'tailoring_order_item_id' => $this->id,
                'completion_date' => $this->item_completion_date,
            ];
        }

        return [
            'id' => $assignment->id,
            'tailoring_order_item_id' => $assignment->tailoring_order_item_id,
            'completion_date' => $assignment->completion_date,
        ];
    }

    private function assignmentUnitCount(array $data = []): int
    {
        $quantity = isset($data['quantity']) ? (float) $data['quantity'] : (float) $this->quantity;

        return max(1, (int) round($quantity));
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
