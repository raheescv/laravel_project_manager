<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class TailoringPayment extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tailoring_order_id',
        'payment_method_id',
        'date',
        'amount',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'tailoring_order_id' => ['required'],
            'payment_method_id' => ['required'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ], $merge);
    }

    protected static function booted()
    {
        static::creating(function ($payment): void {
            if (empty($payment->created_by)) {
                $payment->created_by = auth()->id();
            }
            if (empty($payment->updated_by)) {
                $payment->updated_by = auth()->id();
            }
        });

        static::updating(function ($payment): void {
            if (empty($payment->updated_by)) {
                $payment->updated_by = auth()->id();
            }
        });

        static::saved(function ($payment): void {
            $payment->order->updatePaymentMethods();
        });

        static::deleted(function ($payment): void {
            $payment->order->updatePaymentMethods();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TailoringOrder::class, 'tailoring_order_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByPaymentMethod($query, $methodId)
    {
        return $query->where('payment_method_id', $methodId);
    }

    public function scopeToday($query)
    {
        return $query->where('date', date('Y-m-d'));
    }

    public function scopeLast7Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')]);
    }

    // Accessors
    public function getNameAttribute()
    {
        return $this->paymentMethod?->name;
    }
}
