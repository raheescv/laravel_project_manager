<?php

namespace App\Models;

use App\Enums\Property\BuildingOwnership;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PropertyBuilding extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'property_group_id',
        'name',
        'arabic_name',
        'created_date',
        'reference_code',
        'building_no',
        'location',
        'floors',
        'investment',
        'electricity',
        'road',
        'landmark',
        'amount',
        'ownership',
        'status',
        'account_id',
        'remark',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ownership' => BuildingOwnership::class,
    ];

    public static function rules($id = 0): array
    {
        return [
            'property_group_id' => 'required|exists:property_groups,id',
            'name' => 'required|string|max:255',
            'ownership' => 'required|string',
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
        $self = $self->when($request['property_group_id'] ?? '', function ($query, $value) {
            return $query->where('property_group_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function rentOuts(): HasMany
    {
        return $this->hasMany(RentOut::class, 'property_building_id');
    }
}
