<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailoringCategoryMeasurement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'tailoring_category_id',
        'field_key',
        'label',
        'field_type',
        'options_source',
        'section',
        'sort_order',
        'is_active',
        'is_required',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TailoringCategory::class, 'tailoring_category_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
