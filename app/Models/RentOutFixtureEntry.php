<?php

namespace App\Models;

use App\Enums\RentOut\FixtureStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * One rectification record inside an area's Fixture Comments block: what was wrong,
 * a photo before the work and one after it, and how far along the work is.
 */
class RentOutFixtureEntry extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rent_out_fixture_area_id',
        'before_image_path',
        'after_image_path',
        'comments',
        'status',
        'completed_date',
        'sort_order',
    ];

    protected $casts = [
        'status' => FixtureStatus::class,
        'completed_date' => 'date',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(RentOutFixtureArea::class, 'rent_out_fixture_area_id');
    }

    public function getBeforeImageUrlAttribute(): ?string
    {
        return $this->before_image_path ? asset('storage/'.$this->before_image_path) : null;
    }

    public function getAfterImageUrlAttribute(): ?string
    {
        return $this->after_image_path ? asset('storage/'.$this->after_image_path) : null;
    }
}
