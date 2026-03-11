<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PropertyGroup extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'arabic_name',
        'description',
        'lease_agreement_years',
        'created_by',
        'updated_by',
    ];

    public static function rules($id = 0): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%");
            });
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(PropertyBuilding::class);
    }

    public function rentOuts(): HasMany
    {
        return $this->hasMany(RentOut::class, 'property_group_id');
    }
}
