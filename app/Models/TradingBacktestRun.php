<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBacktestRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'parameters' => 'array',
        'equity_curve' => 'array',
        'trades' => 'array',
        'initial_capital' => 'float',
        'final_equity' => 'float',
        'total_return_percent' => 'float',
        'max_drawdown_percent' => 'float',
        'sharpe' => 'float',
        'sortino' => 'float',
        'win_rate' => 'float',
    ];
}
