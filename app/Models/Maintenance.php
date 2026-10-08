<?php

namespace App\Models;

use App\Enums\Maintenance\MaintenancePriority;
use App\Enums\Maintenance\MaintenanceSegment;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Maintenance extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'property_id',
        'property_group_id',
        'property_building_id',
        'property_type_id',
        'rent_out_id',
        'account_id',
        'date',
        'time',
        'priority',
        'segment',
        'contact_no',
        'remark',
        'company_remark',
        'status',
        'created_by',
        'completed_by',
        'completed_at',
        'updated_by',
    ];

    protected $casts = [
        'status' => MaintenanceStatus::class,
        'priority' => MaintenancePriority::class,
        'segment' => MaintenanceSegment::class,
        'date' => 'date',
        'completed_at' => 'datetime',
    ];

    public static function rules($id = 0): array
    {
        return [
            'date' => 'required|date',
            'property_id' => 'required|exists:properties,id',
            'priority' => 'required',
        ];
    }

    public function setSegmentAttribute($value)
    {
        $this->attributes['segment'] = $value === '' ? null : $value;
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->property_id) {
                $property = $model->property;
                if ($property) {
                    $model->property_type_id = $property->property_type_id;
                    $model->property_building_id = $property->property_building_id;
                    $building = $property->building;
                    if ($building) {
                        $model->property_group_id = $building->property_group_id;
                    }
                }
            }
        });
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class)->withTrashed();
    }

    /** @return BelongsTo<PropertyBuilding, $this> */
    public function building(): BelongsTo
    {
        return $this->belongsTo(PropertyBuilding::class, 'property_building_id')->withTrashed();
    }

    /** @return BelongsTo<PropertyGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    /** @return BelongsTo<PropertyType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /** @return BelongsTo<RentOut, $this> */
    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class, 'rent_out_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return HasMany<MaintenanceComplaint, $this> */
    public function maintenanceComplaints(): HasMany
    {
        return $this->hasMany(MaintenanceComplaint::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
