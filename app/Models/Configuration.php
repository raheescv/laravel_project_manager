<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Configuration extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    /**
     * The `value` column is NOT NULL. Settings forms can submit null for an
     * unset key (e.g. an empty barcode prefix on a fresh tenant), which would
     * violate the constraint. Coerce null to an empty string at the model layer
     * so every configuration write is safe regardless of the caller.
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = $value ?? '';
    }

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'key' => ['required', Rule::unique(self::class)->where('tenant_id', self::getCurrentTenantId())->ignore($id)],
            'value' => ['required'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
