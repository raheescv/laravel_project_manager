<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingCircuitState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'breaker_tripped' => 'bool',
        'trading_day' => 'date',
        'tripped_at' => 'datetime',
        'realized_pnl' => 'float',
        'unrealized_pnl' => 'float',
    ];
}
