<?php

namespace App\Models;

use App\Enums\RentOut\PaymentMode;
use App\Enums\RentOut\SecurityStatus;
use App\Enums\RentOut\SecurityType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutSecurity extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'amount',
        'payment_mode',
        'status',
        'type',
        'due_date',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'payment_mode' => PaymentMode::class,
        'status' => SecurityStatus::class,
        'type' => SecurityType::class,
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public static function rules($id = 0): array
    {
        return [
            'rent_out_id' => 'required|exists:rent_outs,id',
            'amount' => 'required|numeric|min:0',
            'payment_mode' => 'required',
        ];
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }
}
