<?php

return [
    'old_connection' => env('RENT_OUT_COMPARISON_OLD_CONNECTION') ?: 'mysql2',
    'new_connection' => env('RENT_OUT_COMPARISON_NEW_CONNECTION') ?: 'mysql',
    'site_1_url' => env('RENT_OUT_COMPARISON_SITE_1_URL') ?: 'https://accounts.test',
    'site_2_url' => env('RENT_OUT_COMPARISON_SITE_2_URL') ?: (env('APP_URL') ?: 'https://project_manager.test'),
];
