<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SaleReturn extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    const ADDITIONAL_DISCOUNT_DESCRIPTION = 'Additional Discount Provided on Sales Return';

    protected $fillable = [
        'tenant_id',
        'reference_no',
        'branch_id',
        'account_id',
        'date',
        'gross_amount',
        'item_discount',
        'tax_amount',
        'total',
        'other_discount',

        'paid',

        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'date' => ['required'],
        ], $merge);
    }

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeToday($query)
    {
        return $query->where('date', date('Y-m-d'));
    }

    public function scopeLast7Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')]);
    }

    public function scopeLast30Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')]);
    }

    public function scopeCustomerSearch($query, $branch_id = null, $from = null, $to = null)
    {
        return $query->when($branch_id, fn ($q) => $q->where('branch_id', $branch_id))
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to));
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

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SaleReturnPayment::class);
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'SaleReturn');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'SaleReturn');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'model_id')->where('model', 'SaleReturn');
    }
}
