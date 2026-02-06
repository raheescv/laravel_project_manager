<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
                $item->created_by = auth()->id();
            }
            if (empty($item->updated_by)) {
                $item->updated_by = auth()->id();
            }
        });

        static::updating(function ($item): void {
            if (empty($item->updated_by)) {
                $item->updated_by = auth()->id();
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
}
