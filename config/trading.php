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
        // Times use APP_TIMEZONE (Asia/Kolkata) — NSE cash session 09:30–15:30 IST.
        'enabled' => (bool) env('TRADING_SCHEDULE_ENABLED', false),
        'buy_between' => ['09:30', '15:30'],
        'sell_between' => ['09:40', '15:30'],
        'forced_flatten_at' => '15:30',
        'quick_between' => ['09:30', '15:00'],
        'quick_flatten_at' => '15:01',
        'analyse_at' => '16:00',
    ],

    'alerts' => [
        'telegram_chat_id' => env('TRADING_TELEGRAM_CHAT_ID'),
        'log_channel' => env('TRADING_LOG_CHANNEL', 'stack'),
    ],

    'ai' => [
        'model' => env('TRADING_AI_MODEL', 'gpt-4o-mini'),
    ],
];
