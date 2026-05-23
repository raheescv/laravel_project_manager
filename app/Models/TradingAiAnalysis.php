<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingAiAnalysis extends Model
{
    protected $guarded = [];

    protected $casts = [
        'for_date' => 'date',
        'context' => 'array',
        'tokens_used' => 'int',
    ];
}
