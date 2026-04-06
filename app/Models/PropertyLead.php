<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'company_contact_no',
        'source',
        'type',
        'property_group_id',
        'assigned_to',
        'assign_date',
        'country_id',
        'nationality',
        'location',
        'meeting_date',
        'meeting_time',
        // 'meeting_datetime',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'remarks' => 'array',
        'assign_date' => 'date',
        'meeting_date' => 'date',
        'meeting_datetime' => 'datetime',
    ];

    public static function rules($id = 0, array $data = []): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => [ 'nullable', 'string', 'regex:/^[0-9+\-\s]{6,20}$/', ],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', 'in:Sales,Rentout'],
            'source' => ['required', 'string'],
            'status' => ['nullable', 'string', 'max:30'],
            'property_group_id' => ['nullable', 'exists:property_groups,id'],
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getAssigneeNameAttribute(): ?string
    {
        return $this->assignee?->name;
    }
}
