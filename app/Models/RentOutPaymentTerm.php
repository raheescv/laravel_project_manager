<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutPaymentTerm extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'label',
        'amount',
        'discount',
        'total',
        'paid',
        'balance',
        'due_date',
        'paid_date',
        'status',
        'payment_mode',
        'cheque_no',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->total = ($model->amount ?? 0) - ($model->discount ?? 0);
            $model->balance = $model->total - ($model->paid ?? 0);
            if ($model->balance <= 0 && $model->total > 0) {
                $model->status = 'paid';
                $model->paid_date = $model->paid_date ?? now();
            }
        });
    }

    public static function rules($id = 0): array
    {
        return [
            'rent_out_id' => 'required|exists:rent_outs,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
        ];
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')->where('due_date', '<', now());
    }

    public function getPaidFlagAttribute(): string
    {
        if ($this->status === 'paid') {
            return 'Paid';
        }
        if ($this->paid > 0) {
            return 'Partially Paid';
        }
        if ($this->due_date && $this->due_date->isPast()) {
            return 'Pending';
        }

        return 'Current Pending';
    }
}
