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
        'amount',
        'discount',
        'total',
        'due_date',
        'paid_date',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

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
}
