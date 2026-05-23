<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TradingBroker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
        'is_default' => 'bool',
        'config' => 'array',
        'last_healthy_at' => 'datetime',
    ];

    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) {
                    return [];
                }
                try {
                    return json_decode(Crypt::decryptString($value), true) ?? [];
                } catch (\Throwable) {
                    return [];
                }
            },
            set: fn ($value) => $value ? Crypt::encryptString(json_encode($value)) : null,
        );
    }
}
