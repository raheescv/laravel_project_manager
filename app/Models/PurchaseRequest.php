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

        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });
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
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('id', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('branch', function ($q) use ($filters) {
                        $q->where('name', 'like', '%'.$filters['search'].'%');
                    })
                    ->orWhereHas('creator', function ($q) use ($filters) {
                        $q->where('name', 'like', '%'.$filters['search'].'%');
                    })
                    ->orWhereHas('decisionMaker', function ($q) use ($filters) {
                        $q->where('name', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['branch_id']) && $filters['branch_id']) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['created_by']) && $filters['created_by']) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['decision_by']) && $filters['decision_by']) {
            $query->where('decision_by', $filters['decision_by']);
        }

        return $query;
    }
}
