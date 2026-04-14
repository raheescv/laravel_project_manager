<?php

namespace App\Models;

use App\Enums\Property\PropertyStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Property extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'property_group_id',
        'property_building_id',
        'property_type_id',
        'number',
        'code',
        'unit_no',
        'floor',
        'rooms',
        'kitchen',
        'toilet',
        'hall',
        'size',
        'rent',
        'ownership',
        'electricity',
        'kahramaa',
        'parking',
        'furniture',
        'status',
        'availability_status',
        'flag',
        'remark',
        'floor_plan',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => PropertyStatus::class,
        'size' => 'decimal:2',
        'rent' => 'decimal:2',
    ];

    public static function rules($id = 0): array
    {
        return [
            'property_building_id' => 'required|exists:property_buildings,id',
            'property_type_id' => 'required|exists:property_types,id',
            'number' => 'required|string|max:255',
        ];
    }

    public function getDropDownList($request)
    {
        $self = self::with('building', 'group', 'type')->orderBy('number');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('number', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['building_id'] ?? '', function ($query, $value) {
            return $query->where('property_building_id', $value);
        });
        $self = $self->when($request['property_group_id'] ?? '', function ($query, $value) {
            return $query->where('property_group_id', $value);
        });
        $self = $self->when($request['property_type_id'] ?? '', function ($query, $value) {
            return $query->where('property_type_id', $value);
        });
        $self = $self->when($request['vacant_only'] ?? '', function ($query) {
            return $query->vacant();
        });
        $self = $self->when($request['available_only'] ?? '', function ($query) {
            return $query->available();
        });
        $self = $self->limit(10);
        $self = $self->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->number,
                'status' => $item->status?->value ?? $item->status,
                'group' => $item->group?->name,
                'building' => $item->building?->name,
                'type' => $item->type?->name,
                'floor' => $item->floor,
                'rooms' => $item->rooms,
                'size' => $item->size,
                'rent' => $item->rent,
            ];
        })->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(PropertyBuilding::class, 'property_building_id')->withTrashed();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function rentOuts(): HasMany
    {
        return $this->hasMany(RentOut::class);
    }

    public function tenantDetails(): HasMany
    {
        return $this->hasMany(TenantDetail::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->property_building_id && ! $model->property_group_id) {
                $building = PropertyBuilding::find($model->property_building_id);
                if ($building) {
                    $model->property_group_id = $building->property_group_id;
                }
            }
        });
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeVacant($query)
    {
        return $query->where('status', PropertyStatus::Vacant);
    }

    public function scopeEmpty($query)
    {
        return $query->where('status', PropertyStatus::Vacant);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', PropertyStatus::Occupied);
    }
}
