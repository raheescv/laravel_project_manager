<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingStrategy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
        'paper_mode' => 'bool',
        'parameters' => 'array',
    ];
}
