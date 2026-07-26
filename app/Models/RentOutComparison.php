<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RentOutComparison extends Model
{
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
