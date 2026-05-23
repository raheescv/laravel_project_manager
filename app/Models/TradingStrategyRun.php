<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingStrategyRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'ran_at' => 'datetime',
        'score' => 'float',
        'confidence' => 'float',
    ];
}
