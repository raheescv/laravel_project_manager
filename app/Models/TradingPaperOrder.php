<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingPaperOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'price' => 'float',
        'filled_price' => 'float',
        'stop_loss' => 'float',
        'target' => 'float',
        'exit_price' => 'float',
        'pnl' => 'float',
    ];
}
