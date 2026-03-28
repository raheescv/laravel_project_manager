<?php

namespace App\Models;

use App\Enums\PurchaseRequest\PurchaseRequestStatus;
use App\Models\Scopes\AssignedBranchScope;
use App\Policies\PurchaseRequestPolicy;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

#[UsePolicy(PurchaseRequestPolicy::class)]
class PurchaseRequest extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'created_by',
        'status',
        'decision_by',
        'decision_at',
        'decision_note',
    ];

    protected $casts = [
        'status' => PurchaseRequestStatus::class,
        'decision_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(PurchaseRequestProduct::class);
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', PurchaseRequestStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', PurchaseRequestStatus::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', PurchaseRequestStatus::REJECTED);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    // Filter
    public function scopeFilter($query, $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($q) use ($search) {
                $q->where('id', 'like', '%'.$search.'%')
                    ->orWhereHas('branch', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('creator', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('decisionMaker', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    });
            });
        })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['created_by'] ?? null, fn ($q, $createdBy) => $q->where('created_by', $createdBy))
            ->when($filters['decision_by'] ?? null, fn ($q, $decisionBy) => $q->where('decision_by', $decisionBy))
            ->when($filters['from_date'] ?? null, fn ($q, $fromDate) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn ($q, $toDate) => $q->whereDate('created_at', '<=', $toDate));

        return $query;
    }
}
