<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Sale extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    const ADDITIONAL_DISCOUNT_DESCRIPTION = 'Additional Discount Provided on Sales';

    protected $fillable = [
        'invoice_no',
        'reference_no',
        'sale_type',

        'branch_id',
        'account_id',
        'date',
        'due_date',

        'customer_name',
        'customer_mobile',

        'gross_amount',
        'item_discount',
        'tax_amount',

        'total',

        'other_discount',
        'freight',

        'paid',

        'address',

        'status',

        'created_by',
        'updated_by',
        'cancelled_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'invoice_no' => ['required', Rule::unique(self::class, 'invoice_no')->ignore($id)],
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'sale_type' => ['required'],
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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->where('date', date('Y-m-d'));
    }

    public function scopeCurrentBranch($query)
    {
        return CurrentBranchScope::apply($query);
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
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to));
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
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'Sale');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'Sale');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'model_id')->where('model', 'Sale');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('invoice_no');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('sales.invoice_no', 'like', "%{$value}%")
                    ->orWhere('sales.reference_no', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['account_id'] ?? '', function ($query, $value) {
            return $query->where('account_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['invoice_no', 'reference_no', 'id'])->toArray();
        array_unshift($self, [
            'invoice_no' => 'General',
            'reference_no' => null,
            'id' => 0,
        ]);
        $return['items'] = $self;

        return $return;
    }
}
