<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PropertyLead extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'mobile',
        'email',
        'company_name',
        'company_contact_person',
        'company_contact_no',
        'source',
        'sub_source',
        'type',
        'property_group_id',
        'property_type_id',
        'rental_type',
        'budget_min',
        'budget_max',
        'assigned_to',
        'assign_date',
        'reassigned_at',
        'country_id',
        'nationality',
        'location',
        'meeting_date',
        'meeting_time',
        // 'meeting_datetime',
        'remarks',
        'status',
        'sub_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'remarks' => 'array',
        'assign_date' => 'date',
        'reassigned_at' => 'datetime',
        'meeting_date' => 'date',
        'meeting_datetime' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // A first assignment on create is not a reassignment; any later change of salesman is.
        static::updating(function (self $lead): void {
            if ($lead->isDirty('assigned_to') && filled($lead->getOriginal('assigned_to'))) {
                $lead->reassigned_at = now();
            }
        });
    }

    public static function rules($id = 0, array $data = []): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', Rule::in(array_keys(leadTypes()))],
            'source' => ['required', 'string'],
            'sub_source' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'sub_status' => ['nullable', 'string', 'max:255'],
            'property_group_id' => ['nullable', 'exists:property_groups,id'],
            'property_type_id' => ['nullable', 'exists:property_types,id'],
            'rental_type' => ['nullable', Rule::in(array_keys(leadRentalTypes()))],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', ...(is_numeric($data['budget_min'] ?? null) ? ['gte:budget_min'] : [])],
            'country_id' => ['nullable', 'exists:countries,id'],
        ];
    }

    public function scopeCurrentBranch($query)
    {
        if (session('branch_id')) {
            return $query->where('property_leads.branch_id', session('branch_id'));
        }

        return $query;
    }

    /** @return BelongsTo<PropertyGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    /** @return BelongsTo<PropertyType, $this> */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getAssigneeNameAttribute(): ?string
    {
        return $this->assignee?->name;
    }
}
