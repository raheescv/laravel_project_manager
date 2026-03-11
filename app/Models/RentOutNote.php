<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutNote extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'rent_out_id',
        'note',
        'created_by',
    ];

    public static function rules($id = 0): array
    {
        return [
            'rent_out_id' => 'required|exists:rent_outs,id',
            'note' => 'required|string',
        ];
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
