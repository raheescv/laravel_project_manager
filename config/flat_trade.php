<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FlatTrade PiConnect Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FlatTrade PiConnect API integration.
    | Set these values in your .env file.
    |
    */

    'base_url' => env('FLAT_TRADE_BASE_URL', 'https://piconnect.flattrade.in/PiConnectTP'),
    'auth_api_url' => env('FLAT_TRADE_AUTH_API_URL', 'https://authapi.flattrade.in'),
    'api_key' => env('FLAT_TRADE_API_KEY'),
    'api_secret' => env('FLAT_TRADE_API_SECRET'),
    'client_id' => env('FLAT_TRADE_CLIENT_ID'),
    'client_secret' => env('FLAT_TRADE_CLIENT_SECRET'),
    'j_key' => env('FLAT_TRADE_J_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default values for common API parameters
    |
    */

    'defaults' => [
        'exchange' => env('FLAT_TRADE_DEFAULT_EXCHANGE', 'NSE'),
        'product' => env('FLAT_TRADE_DEFAULT_PRODUCT', 'C'), // C=CNC, H=Cover, B=Bracket
        'validity' => env('FLAT_TRADE_DEFAULT_VALIDITY', 'DAY'),
        'order_source' => env('FLAT_TRADE_ORDER_SOURCE', 'API'),
        'market_protection' => env('FLAT_TRADE_MARKET_PROTECTION', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache settings for tokens and API responses
    |
    */

    'cache' => [
        'token_ttl' => env('FLAT_TRADE_TOKEN_TTL', 55), // minutes
        'client_id_ttl' => env('FLAT_TRADE_CLIENT_ID_TTL', 30), // days
        'enable_response_cache' => env('FLAT_TRADE_ENABLE_RESPONSE_CACHE', false),
        'response_cache_ttl' => env('FLAT_TRADE_RESPONSE_CACHE_TTL', 5), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Logging configuration for API requests and responses
    |
    */

    'logging' => [
        'log_requests' => env('FLAT_TRADE_LOG_REQUESTS', true),
        'log_responses' => env('FLAT_TRADE_LOG_RESPONSES', false),
        'log_level' => env('FLAT_TRADE_LOG_LEVEL', 'info'),
    ],
];
