<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailoringMeasurementOption extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'option_type',
        'value',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('option_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('value');
    }

    public static function getOptionsByType($type)
    {
        $tenantId = self::getCurrentTenantId();
        
        return self::where('tenant_id', $tenantId)
            ->where('option_type', $type)
            ->orderBy('value')
            ->pluck('value', 'id')
            ->toArray();
    }
}
