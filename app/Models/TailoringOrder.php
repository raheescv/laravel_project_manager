<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class TailoringOrder extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'order_no',
        'branch_id',
        'account_id',
        'customer_name',
        'customer_mobile',
        'salesman_id',
        'order_date',
        'delivery_date',
        'gross_amount',
        'item_discount',
        'tax_amount',
        'total',
        'other_discount',
        'freight',
        'round_off',
        'grand_total',
        'paid',
        'balance',
        'payment_method_ids',
        'payment_method_name',
        'status',
        'notes',
        'rack_id',
        'cutter_id',
        'completion_date',
        'completion_status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'completion_date' => 'date:Y-m-d',
        'gross_amount' => 'decimal:2',
        'item_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'other_discount' => 'decimal:2',
        'freight' => 'decimal:2',
        'round_off' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'order_no' => ['required', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
            'order_date' => ['required', 'date'],
        ], $merge);
    }

    protected static function booted()
    {
        static::creating(function ($order): void {
            if (empty($order->order_no)) {
                $order->order_no = self::generateOrderNo();
            }
            if (empty($order->created_by)) {
                $order->created_by = auth()->id();
            }
        });

        static::updating(function ($order): void {
            if (empty($order->updated_by)) {
                $order->updated_by = auth()->id();
            }
        });
    }

    public static function generateOrderNo()
    {
        // Generate order number like RA3HAWO2404
        $prefix = 'TA'; // Tailoring prefix
        $year = date('y');
        $month = date('m');
        $day = date('d');
        $random = strtoupper(substr(uniqid(), -4));

        return $prefix.$year.$month.$day.$random;
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'rack_id');
    }

    public function cutter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cutter_id');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TailoringOrderItem::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(TailoringOrderMeasurement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TailoringPayment::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('order_date', $date);
    }

    public function scopeByCustomer($query, $customerName)
    {
        return $query->where('customer_name', 'like', "%{$customerName}%");
    }

    public function scopeByCompletionStatus($query, $status)
    {
        return $query->where('completion_status', $status);
    }

    // Methods
    public function calculateTotals()
    {
        $this->gross_amount = $this->items->sum('gross_amount');
        $this->item_discount = $this->items->sum('discount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total = $this->gross_amount - $this->item_discount + $this->tax_amount;
        $this->grand_total = ($this->total - $this->other_discount + $this->freight) + $this->round_off;
        $this->paid = $this->payments->sum('amount');
        $this->balance = $this->grand_total - $this->paid;
    }

    public function updateTotals()
    {
        $this->calculateTotals();
        $this->save();
    }

    public function updatePaymentMethods()
    {
        $paymentMethodIds = $this->payments->pluck('payment_method_id')->toArray();
        $paymentMethodNames = Account::whereIn('id', $paymentMethodIds)->pluck('name')->toArray();

        $this->payment_method_ids = implode(',', $paymentMethodIds);
        $this->payment_method_name = implode(', ', $paymentMethodNames);
        $this->paid = $this->payments->sum('amount');
        $this->balance = $this->grand_total - $this->paid;
        $this->save();
    }
}
