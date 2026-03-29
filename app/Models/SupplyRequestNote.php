<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyRequestNote extends Model
{
    protected $fillable = [
        'supply_request_id',
        'note',
        'created_by',
    ];

    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
