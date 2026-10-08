<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class TailoringOrderMeasurement extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tailoring_order_id',

        'tailoring_category_id',
        'tailoring_category_model_id',
        'tailoring_category_model_type_id',

        'data',

        'tailoring_notes',

        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

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
    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<TailoringOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(TailoringOrder::class, 'tailoring_order_id');
    }

    /** @return BelongsTo<TailoringCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TailoringCategory::class, 'tailoring_category_id');
    }

    /** @return BelongsTo<TailoringCategoryModel, $this> */
    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(TailoringCategoryModel::class, 'tailoring_category_model_id');
    }

    /** @return BelongsTo<TailoringCategoryModelType, $this> */
    public function categoryModelType(): BelongsTo
    {
        return $this->belongsTo(TailoringCategoryModelType::class, 'tailoring_category_model_type_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('tailoring_category_id', $categoryId);
    }
}
