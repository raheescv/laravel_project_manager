<?php

/**
 * Central config for the trading platform.
 *
 * Anything tunable lives here so you don't have to redeploy to change risk
 * limits, AI model, or which broker is the default.
 */
return [
    'default_broker' => env('TRADING_DEFAULT_BROKER', 'flat_trade'),

    'timezone' => env('TRADING_TIMEZONE', 'Asia/Kolkata'),

    'risk' => [
        'max_position_size' => env('TRADING_MAX_POSITION_SIZE', 50000),
        'max_concurrent_positions' => env('TRADING_MAX_CONCURRENT', 10),
        'max_daily_loss' => env('TRADING_MAX_DAILY_LOSS', 5000),
        'cooldown_minutes' => env('TRADING_COOLDOWN_MINUTES', 15),
    ],

    'schedule' => [
        // Toggle to flip the live trade:* schedule on without touching code.
        'enabled' => (bool) env('TRADING_SCHEDULE_ENABLED', false),
        'buy_between' => ['05:10', '09:55'],
        'sell_between' => ['05:20', '09:55'],
        'forced_flatten_at' => '09:55',
        'quick_between' => ['04:30', '09:30'],
        'quick_flatten_at' => '09:31',
        'analyse_at' => '10:30',
    ],

    'alerts' => [
        'telegram_chat_id' => env('TRADING_TELEGRAM_CHAT_ID'),
        'log_channel' => env('TRADING_LOG_CHANNEL', 'stack'),
    ],

    'ai' => [
        'model' => env('TRADING_AI_MODEL', 'gpt-4o-mini'),
    ],
];
