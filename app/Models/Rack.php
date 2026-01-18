<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Rack extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TailoringOrder::class, 'rack_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
