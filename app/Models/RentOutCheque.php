<?php

namespace App\Models;

use App\Enums\RentOut\ChequeStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutCheque extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'cheque_no',
        'bank_name',
        'amount',
        'date',
        'status',
        'payee_name',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'status' => ChequeStatus::class,
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public static function rules($id = 0): array
    {
        return [
            'rent_out_id' => 'required|exists:rent_outs,id',
            'cheque_no' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }
}
