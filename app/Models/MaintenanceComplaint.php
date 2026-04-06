<?php

namespace App\Models;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class MaintenanceComplaint extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'maintenance_id',
        'complaint_id',
        'status',
        'technician_id',
        'technician_remark',
        'assigned_by',
        'assigned_at',
        'completed_by',
        'completed_at',
        'supply_request_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => MaintenanceComplaintStatus::class,
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function rules($id = 0): array
    {
        return [
            'maintenance_id' => 'required|exists:maintenances,id',
            'complaint_id' => 'required|exists:complaints,id',
        ];
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class);
    }
}
