<?php

namespace App\Models;

use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutChecklistSignature extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rent_out_id',
        'phase',
        'role',
        'user_id',
        'signer_name',
        'signature_path',
        'signed_at',
    ];

    protected $casts = [
        'phase' => ChecklistPhase::class,
        'role' => ChecklistSignatoryRole::class,
        'signed_at' => 'datetime',
    ];

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class, 'rent_out_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->signature_path) {
            return null;
        }
        $path = preg_replace('#^public/#', '', $this->signature_path);

        return Storage::disk('public')->url($path);
    }
}
