<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingAlertRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
        'conditions' => 'array',
        'channels' => 'array',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(TradingAlert::class, 'alert_rule_id');
    }
}
