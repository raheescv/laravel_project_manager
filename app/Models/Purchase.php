<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Purchase extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'branch_id',
        'account_id',
        'date',
        'delivery_date',
        'gross_amount',
        'item_discount',
        'tax_amount',
        'total',
        'other_discount',
        'freight',
        'paid',
        'address',
        'status',
        'signature',
        'created_by',
        'updated_by',
        'cancelled_by',
        'deleted_by',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'date' => ['required'],
            'invoice_no' => ['required'],
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
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'Purchase');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'Purchase');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'model_id')->where('model', 'Purchase');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('invoice_no');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('sales.invoice_no', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['account_id'] ?? '', function ($query, $value) {
            return $query->where('account_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['invoice_no', 'id'])->toArray();
        array_unshift($self, ['invoice_no' => 'General', 'id' => 0]);
        $return['items'] = $self;

        return $return;
    }

    public static function updatePurchasePaymentMethods(Purchase $purchase): void
    {
        $data = [
            'paid' => $purchase->payments->sum('amount'),
        ];
        $purchase->update($data);
    }
}
