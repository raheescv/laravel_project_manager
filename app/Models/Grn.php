<?php

namespace App\Models;

use App\Enums\Grn\GrnStatus;
use App\Policies\GrnPolicy;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(GrnPolicy::class)]
class Grn extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'grn_no',
        'tenant_id',
        'branch_id',
        'local_purchase_order_id',
        'vendor_id',
        'date',
        'created_by',
        'status',
        'remarks',
        'decision_by',
        'decision_at',
        'decision_note',
    ];

    protected $casts = [
        'status' => GrnStatus::class,
        'decision_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'vendor_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function localPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(LocalPurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'Grn');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'Grn');
    }

    public function scopePending($query)
    {
        return $query->where('status', GrnStatus::PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', GrnStatus::ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', GrnStatus::REJECTED);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeFilter($query, $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $term) {
                $term = trim($term);
                $query->where(function ($q) use ($term) {
                    $q->where('grn_no', 'like', '%'.$term.'%')
                        ->orWhereHas('localPurchaseOrder', fn ($q) => $q->where('id', 'like', '%'.$term.'%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('decisionMaker', fn ($q) => $q->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['branch_id'] ?? null, fn ($query, $value) => $query->where('branch_id', $value))
            ->when($filters['vendor_id'] ?? null, fn ($query, $value) => $query->where('vendor_id', $value))
            ->when($filters['created_by'] ?? null, fn ($query, $value) => $query->where('created_by', $value))
            ->when($filters['decision_by'] ?? null, fn ($query, $value) => $query->where('decision_by', $value))
            ->when($filters['local_purchase_order_id'] ?? null, fn ($query, $value) => $query->where('local_purchase_order_id', $value))
            ->when($filters['from_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '>=', date('Y-m-d', strtotime($value))))
            ->when($filters['to_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '<=', date('Y-m-d', strtotime($value))));
    }
}
