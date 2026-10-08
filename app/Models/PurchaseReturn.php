<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PurchaseReturn extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'invoice_no',
        'branch_id',
        'account_id',
        'date',
        'gross_amount',
        'item_discount',
        'tax_amount',
        'total',
        'other_discount',
        'freight',
        'paid',
        'status',
        'reason',
        'created_by',
        'updated_by',
        'cancelled_by',
        'deleted_by',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'invoice_no' => ['required'],
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'date' => ['required'],
        ], $merge);
    }

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function scopeCurrentBranch($query)
    {
        return CurrentBranchScope::apply($query);
    }

    public function scopeLast7Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')]);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<PurchaseReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /** @return HasMany<PurchaseReturnPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchaseReturnPayment::class);
    }

    /** @return HasOne<Journal, $this> */
    public function journal(): HasOne
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'PurchaseReturn');
    }

    /** @return HasMany<Journal, $this> */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'PurchaseReturn');
    }

    /** @return HasMany<Ledger, $this> */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'model_id')->where('model', 'PurchaseReturn');
    }
}
