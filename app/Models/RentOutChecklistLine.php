<?php

namespace App\Models;

use App\Enums\RentOut\ChecklistItemStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutChecklistLine extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rent_out_id',
        'checklist_id',
        'image_path',
        'qty',
        'move_in_status',
        'move_in_comment',
        'move_out_status',
        'move_out_comment',
        'damage_cost',
        'sort_order',
    ];

    protected $casts = [
        'move_in_status' => ChecklistItemStatus::class,
        'move_out_status' => ChecklistItemStatus::class,
        'damage_cost' => 'decimal:2',
    ];

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class, 'rent_out_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    /** Item name comes from the master checklist record (2NF). */
    public function getNameAttribute(): ?string
    {
        return $this->item?->name;
    }

    /** Category comes from the master checklist record (2NF). */
    public function getCategoryAttribute(): ?string
    {
        return $this->item?->category;
    }

    /**
     * Relative storage path of the image to display: the line's own upload when
     * present, otherwise the master checklist item's image. Null when neither set.
     */
    public function getResolvedImagePathAttribute(): ?string
    {
        return $this->image_path ?: $this->item?->image_path;
    }

    /** Web-facing URL for the resolved image, or null when none. */
    public function getResolvedImageUrlAttribute(): ?string
    {
        $path = $this->resolved_image_path;

        return $path ? asset('storage/'.$path) : null;
    }
}
