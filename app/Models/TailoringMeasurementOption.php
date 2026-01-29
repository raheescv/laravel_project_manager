<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class TailoringMeasurementOption extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'option_type',
        'value',
    ];

    public static function rules($id = 0, $optionType = null, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'option_type' => ['required', 'string', 'max:255'],
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique(self::class)
                    ->where('tenant_id', $tenantId)
                    ->where('option_type', $optionType ?? request('option_type'))
                    ->ignore($id),
            ],
        ], $merge);
    }

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
