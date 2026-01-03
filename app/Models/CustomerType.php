<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class CustomerType extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'discount_percentage',
    ];

    protected static function getCurrentTenantId(): ?int
    {
        return app(TenantService::class)->getCurrentTenantId();
    }

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', 'string', 'max:255', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
