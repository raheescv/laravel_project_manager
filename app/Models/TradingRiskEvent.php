<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingRiskEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];
}
