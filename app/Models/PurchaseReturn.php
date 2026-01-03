<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PurchaseReturn extends Model implements AuditableContracts
{
    use Auditable;
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
        static::addGlobalScope(new TenantScope());
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchaseReturnPayment::class);
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'PurchaseReturn');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'PurchaseReturn');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'model_id')->where('model', 'PurchaseReturn');
    }
}
