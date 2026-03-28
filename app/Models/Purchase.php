<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Purchase extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'invoice_no',
        'branch_id',
        'account_id',
        'local_purchase_order_id',
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
        'decision_by',
        'decision_at',
        'decision_note',
        'signature',
        'created_by',
        'updated_by',
        'cancelled_by',
        'deleted_by',
    ];

    protected $casts = [
        'decision_at' => 'datetime',
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

    public function localPurchaseOrder()
    {
        return $this->belongsTo(LocalPurchaseOrder::class);
    }

    public function decisionMaker()
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function scopeDecisionPending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDecisionAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDecisionRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeLpoBased($query)
    {
        return $query->whereNotNull('local_purchase_order_id');
    }

    public function scopeLpoPurchaseFilter($query, $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $term) {
                $term = trim($term);
                $query->where(function ($q) use ($term) {
                    $q->where('invoice_no', 'like', '%'.$term.'%')
                        ->orWhereHas('account', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('createdUser', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('decisionMaker', fn ($q) => $q->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['branch_id'] ?? null, fn ($query, $value) => $query->where('branch_id', $value))
            ->when($filters['vendor_id'] ?? null, fn ($query, $value) => $query->where('account_id', $value))
            ->when($filters['created_by'] ?? null, fn ($query, $value) => $query->where('created_by', $value))
            ->when($filters['decision_by'] ?? null, fn ($query, $value) => $query->where('decision_by', $value))
            ->when($filters['local_purchase_order_id'] ?? null, fn ($query, $value) => $query->where('local_purchase_order_id', $value))
            ->when($filters['from_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '>=', date('Y-m-d', strtotime($value))))
            ->when($filters['to_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '<=', date('Y-m-d', strtotime($value))));
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
