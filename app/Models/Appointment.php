<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Appointment extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'account_id',
        'start_time',
        'end_time',
        'color',
        'status',
        'notes',
        'sale_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(AppointmentItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNoResponse($query)
    {
        return $query->where('status', 'no response');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['branch_id'] ?? '', fn ($q, $value) => $q->where('appointments.branch_id', $value))
            ->when($filters['customer_id'] ?? '', fn ($q, $value) => $q->where('appointments.account_id', $value))
            ->when($filters['status'] ?? '', fn ($q, $value) => $q->where('appointments.status', $value))
            ->when($filters['from_date'] ?? '', fn ($q, $value) => $q->whereDate('appointments.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($filters['to_date'] ?? '', fn ($q, $value) => $q->whereDate('appointments.date', '<=', date('Y-m-d', strtotime($value))));
    }
}
