<?php

namespace App\Models;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Policies\LocalPurchaseOrderPolicy;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(LocalPurchaseOrderPolicy::class)]
class LocalPurchaseOrder extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'vendor_id',
        'date',
        'total',
        'decision_by',
        'decision_at',
        'decision_note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => LocalPurchaseOrderStatus::class,
        'decision_at' => 'datetime',
    ];

    protected static function booted() {}

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LocalPurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', LocalPurchaseOrderStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', LocalPurchaseOrderStatus::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', LocalPurchaseOrderStatus::REJECTED);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    // Filter
    public function scopeFilter($query, $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $term) {
                $term = trim($term);
                $query->where(function ($q) use ($term) {
                    $q->where('id', 'like', '%'.$term.'%')
                        ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('decisionMaker', fn ($q) => $q->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['branch_id'] ?? null, fn ($query, $value) => $query->where('branch_id', $value))
            ->when($filters['created_by'] ?? null, fn ($query, $value) => $query->where('created_by', $value))
            ->when($filters['decision_by'] ?? null, fn ($query, $value) => $query->where('decision_by', $value))
            ->when($filters['vendor_id'] ?? null, fn ($query, $value) => $query->where('vendor_id', $value))
            ->when($filters['from_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '>=', date('Y-m-d', strtotime($value))))
            ->when($filters['to_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '<=', date('Y-m-d', strtotime($value))));
    }
}
