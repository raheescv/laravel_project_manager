<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutUtilityTerm extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'utility_id',
        'amount',
        'balance',
        'paid',
        'payment_mode',
        'paid_date',
        'date',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'paid' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->balance = $model->amount - ($model->paid ?? 0);
        });
    }

    public static function rules($id = 0): array
    {
        return [
            'rent_out_id' => 'required|exists:rent_outs,id',
            'utility_id' => 'required|exists:utilities,id',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function utility(): BelongsTo
    {
        return $this->belongsTo(Utility::class);
    }
}
