<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'delivered_to' => 'array',
        'failed_channels' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TradingAlertRule::class, 'alert_rule_id');
    }
}
