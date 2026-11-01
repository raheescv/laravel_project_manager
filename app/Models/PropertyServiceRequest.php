<?php

namespace App\Models;

use App\Enums\Property\ServiceRequestStatus;
use App\Enums\Property\ServiceRequestType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A request to connect or disconnect a utility (Wifi, AC…) on a unit, walked
 * through approve → financial approve → complete.
 */
class PropertyServiceRequest extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    /**
     * The rent-out column that says whether a utility comes with the rent,
     * keyed by the utility's lower-cased name.
     *
     * @var array<string, string>
     */
    public const RENT_OUT_INCLUSION_COLUMNS = [
        'wifi' => 'include_wifi',
        'ac' => 'include_ac',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'property_id',
        'property_group_id',
        'property_building_id',
        'property_type_id',
        'rent_out_id',
        'utility_id',
        'type',
        'date',
        'amount',
        'rent_out_service_status',
        'status',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'financial_approved_by',
        'financial_approved_at',
        'completed_by',
        'completed_at',
        'remarks',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => ServiceRequestType::class,
        'status' => ServiceRequestStatus::class,
        'date' => 'date',
        'amount' => 'decimal:2',
        'notes' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'financial_approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function rules($id = 0): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'utility_id' => ['required', 'exists:utilities,id'],
            'type' => ['required', Rule::enum(ServiceRequestType::class)],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->isDirty('property_id') && $model->property) {
                $model->property_type_id = $model->property->property_type_id;
                $model->property_building_id = $model->property->property_building_id;
                $model->property_group_id = $model->property->property_group_id ?: $model->property->building?->property_group_id;
            }
        });
    }

    /**
     * Whether the occupied rent-out already includes this utility in its rent
     * ('Included' / 'Excluded'), or null when there is no such rent-out or the
     * utility has no inclusion column.
     */
    public static function rentOutServiceStatus(?RentOut $rentOut, ?Utility $utility): ?string
    {
        $column = self::RENT_OUT_INCLUSION_COLUMNS[strtolower(trim((string) $utility?->name))] ?? null;

        return $rentOut && $column ? $rentOut->{$column} : null;
    }

    /**
     * One row per connect, carrying the first disconnect of the same unit and
     * utility that followed it (null while it is still connected).
     */
    public function scopeConnectionPeriods(Builder $query): Builder
    {
        $disconnect = self::withoutGlobalScopes()
            ->selectRaw('MIN(disconnects.date)')
            ->from('property_service_requests as disconnects')
            ->whereColumn('disconnects.tenant_id', 'property_service_requests.tenant_id')
            ->whereColumn('disconnects.property_id', 'property_service_requests.property_id')
            ->whereColumn('disconnects.utility_id', 'property_service_requests.utility_id')
            ->whereColumn('disconnects.date', '>', 'property_service_requests.date')
            ->where('disconnects.type', ServiceRequestType::Disconnect)
            ->whereNull('disconnects.deleted_at');

        return $query
            ->where('property_service_requests.type', ServiceRequestType::Connect)
            ->select('property_service_requests.*')
            ->addSelect(['disconnect_date' => $disconnect]);
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

    /** @return BelongsTo<RentOut, $this> */
    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class, 'rent_out_id');
    }

    /** @return BelongsTo<Utility, $this> */
    public function utility(): BelongsTo
    {
        return $this->belongsTo(Utility::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function financialApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'financial_approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
