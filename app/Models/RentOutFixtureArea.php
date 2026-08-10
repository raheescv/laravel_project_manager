<?php

namespace App\Models;

use App\Enums\RentOut\FixtureStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * The Fixture Comments block for one area (checklist category) of a rent-out unit.
 * Holds the area's rectification entries and the owner's acceptance signature —
 * one signature per area, covering the work recorded under it.
 */
class RentOutFixtureArea extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rent_out_id',
        'category',
        'sort_order',
        'owner_name',
        'owner_user_id',
        'owner_signature_path',
        'owner_signed_at',
    ];

    protected $casts = [
        'owner_signed_at' => 'datetime',
    ];

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class, 'rent_out_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RentOutFixtureEntry::class, 'rent_out_fixture_area_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function getOwnerSignatureUrlAttribute(): ?string
    {
        if (! $this->owner_signature_path) {
            return null;
        }

        return asset('storage/'.preg_replace('#^public/#', '', $this->owner_signature_path));
    }

    public function isSigned(): bool
    {
        return (bool) $this->owner_signature_path;
    }

    /**
     * The owner is asked to accept an area once its work is done — so the pad only
     * opens when the area has entries and every one of them reads Completed.
     */
    public function isReadyForAcceptance(): bool
    {
        if ($this->entries->isEmpty()) {
            return false;
        }

        return $this->entries->every(fn ($e) => $e->status === FixtureStatus::Completed);
    }

    /** @return array<string, int> status value => count, for the block's summary pills. */
    public function statusCounts(): array
    {
        return $this->entries
            ->groupBy(fn ($e) => $e->status?->value ?? FixtureStatus::Pending->value)
            ->map->count()
            ->all();
    }
}
