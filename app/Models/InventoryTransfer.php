<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class InventoryTransfer extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'date',
        'branch_id',
        'from_branch_id',
        'to_branch_id',
        'description',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'signature',
        'updated_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'date' => ['required'],
        ], $merge);
    }

    public static function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public static function scopeCurrentBranch($query)
    {
        return $query->where('branch_id', session('branch_id'));
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** @return HasMany<InventoryTransferItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    /** @return HasMany<Journal, $this> */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'InventoryTransfer');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<Branch, $this> */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }
}
