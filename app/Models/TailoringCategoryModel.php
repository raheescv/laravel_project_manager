<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class TailoringCategoryModel extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'tailoring_category_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function rules($id = 0, $tailoringCategoryId = null, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();
        $categoryId = $tailoringCategoryId ?? request('tailoring_category_id');

        return array_merge([
            'tailoring_category_id' => ['required', 'exists:tailoring_categories,id'],
            'name' => ['required', 'max:255', Rule::unique(self::class)->where('tenant_id', $tenantId)->where('tailoring_category_id', $categoryId)->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TailoringCategory::class, 'tailoring_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('tailoring_category_id', $categoryId);
    }
}
