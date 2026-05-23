<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBar extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'ts' => 'datetime',
        'open' => 'float',
        'high' => 'float',
        'low' => 'float',
        'close' => 'float',
        'volume' => 'int',
    ];
}
