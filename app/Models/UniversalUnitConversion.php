<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class UniversalUnitConversion extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        static::saved(function (UniversalUnitConversion $m): void {
            Cache::increment('uom_version_'.($m->tenant_id ?? static::getCurrentTenantId()));
        });
        static::deleted(function (UniversalUnitConversion $m): void {
            Cache::increment('uom_version_'.($m->tenant_id ?? static::getCurrentTenantId()));
        });
    }

    protected $fillable = [
        'tenant_id',
        'base_unit_id',
        'sub_unit_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'double',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'base_unit_id' => ['required', 'exists:units,id'],
            'sub_unit_id' => ['required', 'exists:units,id', 'different:base_unit_id'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
        ], $merge);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function subUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sub_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
