<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'fyers' => [
        'url' => env('FYERS_FYERS_URL'),
        'client_id' => env('FYERS_CLIENT_ID'),
        'secret_id' => env('FYERS_SECRET_ID'),
        'access_token' => env('FYERS_ACCESS_TOKEN'),
        'refresh_token' => env('FYERS_REFRESH_TOKEN'),
        'auth_code' => env('FYERS_AUTH_CODE'),
    ],
    'id_scanner' => [
        'url' => env('ID_SCANNER_URL', 'http://localhost:5000'),
    ],
    'flat_trade' => [
        'base_url' => env('FLAT_TRADE_BASE_URL', 'https://piconnect.flattrade.in/PiConnectTP'),
        'auth_url' => env('FLAT_TRADE_AUTH_URL', 'https://auth.flattrade.in'),
        'auth_api_url' => env('FLAT_TRADE_AUTH_API_URL', 'https://authapi.flattrade.in'),
        'api_key' => env('FLAT_TRADE_API_KEY'),
        'api_secret' => env('FLAT_TRADE_API_SECRET'),
        'client_id' => env('FLAT_TRADE_CLIENT_ID'),
        'client_secret' => env('FLAT_TRADE_CLIENT_SECRET'),
        'j_key' => env('FLAT_TRADE_J_KEY'),
    ],
];
