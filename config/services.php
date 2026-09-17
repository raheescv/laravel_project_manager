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
    'id_scanner' => [
        'url' => env('ID_SCANNER_URL', 'http://localhost:5000'),
    ],
    'flat_trade' => [
        'base_url' => env('FLAT_TRADE_BASE_URL', 'https://piconnect.flattrade.in/PiConnectAPI'),
        'auth_url' => env('FLAT_TRADE_AUTH_URL', 'https://auth.flattrade.in'),
        'auth_api_url' => env('FLAT_TRADE_AUTH_API_URL', 'https://authapi.flattrade.in'),
        'api_key' => env('FLAT_TRADE_API_KEY', ''),
        'api_secret' => env('FLAT_TRADE_API_SECRET', ''),
        'client_id' => env('FLAT_TRADE_CLIENT_ID', ''),
        'client_secret' => env('FLAT_TRADE_CLIENT_SECRET', ''),
        'j_key' => env('FLAT_TRADE_J_KEY', ''),
    ],
    'shopify' => [
        'store_url' => env('SHOPIFY_STORE_URL', 'https://ahlanfun.myshopify.com'),
        'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
        'api_version' => env('SHOPIFY_API_VERSION', '2024-10'),
    ],
    'meta_whatsapp' => [
        'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
        'template_name' => env('META_WHATSAPP_TEMPLATE_NAME', 'invoice_slip'),
        'base_url' => env('META_WHATSAPP_BASE_URL', 'https://wa-api.cloud/api/v1'),
    ],
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'meta'),
    ],
    'core_connecta' => [
        'url' => env('CORE_CONNECTA_URL', 'http://localhost:3000'),
        'api_key' => env('CORE_CONNECTA_API_KEY', ''),
        'session_id' => env('CORE_CONNECTA_SESSION_ID', ''),
        'session_name' => env('CORE_CONNECTA_SESSION_NAME', 'Project Manager'),
    ],
    'pusher' => [
        'pusher_app_key' => env('PUSHER_APP_KEY'),
        'pusher_app_cluster' => env('PUSHER_APP_CLUSTER'),
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],
    'gemini' => [
        'image_models' => env('GEMINI_IMAGE_MODELS', 'gemini-2.5-flash-image-preview,gemini-3-pro-image-preview'),
    ],
    'openai' => [
        'image_models' => env('OPENAI_IMAGE_MODELS', 'dall-e-3'),
    ],
    // Tap Payments (storefront checkout). Keys are per tenant, in Settings → Online Payments.
    'tap' => [
        'base_url' => env('TAP_BASE_URL', 'https://api.tap.company/v2'),
    ],
    // QCB QPay EZ-Connect (student card top-ups). Payment, inquiry and refund share one URL.
    // Merchant credentials are per tenant, encrypted, in Settings -> Student Cards.
    'qpay' => [
        'staging_url' => env('QPAY_STAGING_URL', 'https://pguat.qcb.gov.qa/qcb-pg/api/gateway/2.0'),
        'production_url' => env('QPAY_PRODUCTION_URL', 'https://pg-api.qpay.gov.qa/qcb-pg/api/gateway/2.0'),
        // QPay dates (ddMMyyyyHHmmss) are Qatar time whatever the app timezone is.
        'timezone' => 'Asia/Qatar',
    ],
    // The standalone parent_portal app. Used for invite links and the way back from
    // QPay when a school has not set its own address in Settings -> Student Settings.
    'parent_portal' => [
        'url' => env('PARENT_PORTAL_URL'),
    ],
    // QZ Tray silent label printing. One self-signed pair for the whole app: php artisan qz:certificate
    'qz' => [
        'certificate' => env('QZ_CERTIFICATE_PATH', storage_path('app/private/qz/digital-certificate.txt')),
        'private_key' => env('QZ_PRIVATE_KEY_PATH', storage_path('app/private/qz/private-key.pem')),
    ],
];
