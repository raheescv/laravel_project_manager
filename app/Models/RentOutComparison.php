<?php

namespace App\Models;

use App\Traits\BelongsToTenantThroughParent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RentOutComparison extends Model
{
    use BelongsToTenantThroughParent;

    public function tenantParentRelation(): string
    {
        return 'rentOut';
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<RentOut, $this> */
    public function rentOut(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    protected $fillable = [
        'rent_out_id',
        'agreement_type',
        'category',
        'status',
        'is_booking',
        'exists_old',
        'exists_new',
        'matches',
        'difference_count',
        'old_url',
        'new_url',
        'payload',
        'compared_at',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'is_booking' => 'boolean',
            'exists_old' => 'boolean',
            'exists_new' => 'boolean',
            'matches' => 'boolean',
            'payload' => 'array',
            'compared_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function scopeDiffering(Builder $query): Builder
    {
        return $query->where('matches', false);
    }
}
