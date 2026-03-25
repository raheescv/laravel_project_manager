<?php

namespace App\Models;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Enums\PurchaseOrder\PurchaseOrderStatus;
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
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'vendor_id',
        'tenant_id',
        'branch_id',
        'created_by',
        'total_amount',
        'decision_by',
        'decision_at',
        'decision_note',
        'status',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'decision_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });
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
        if (isset($filters['search']) && $filters['search']) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('id', 'like', '%' . $term . '%')
                    ->orWhereHas('branch', function ($q) use ($term) {
                        $q->where('name', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('creator', function ($q) use ($term) {
                        $q->where('name', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('decisionMaker', function ($q) use ($term) {
                        $q->where('name', 'like', '%' . $term . '%');
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

        if (isset($filters['vendor_id']) && $filters['vendor_id']) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['product_id']) && $filters['product_id']) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        return $query;
    }
}
