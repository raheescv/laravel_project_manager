<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class Unit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', 'max:20', Rule::unique(self::class, 'name')->where('tenant_id', $tenantId)->ignore($id)],
            'code' => ['required', 'max:20', Rule::unique(self::class, 'code')->where('tenant_id', $tenantId)->ignore($id)],
        ], $merge);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = trim($value);
    }

    /**
     * The unit a new product starts with: "Nos" when the tenant has it, otherwise the oldest unit.
     */
    public static function defaultBaseUnit(): ?self
    {
        return self::query()->where(fn ($query) => $query->where('name', 'Nos')->orWhere('code', 'Nos'))->first(['id', 'name'])
            ?? self::query()->orderBy('id')->first(['id', 'name']);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%");
            });
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'code', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
