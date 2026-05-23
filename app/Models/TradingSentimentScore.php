<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingSentimentScore extends Model
{
    protected $guarded = [];

    protected $casts = [
        'observed_at' => 'datetime',
        'score' => 'float',
        'confidence' => 'float',
    ];
}
