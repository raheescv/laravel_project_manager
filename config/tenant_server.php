<?php

/*
|--------------------------------------------------------------------------
| Tenant server sync
|--------------------------------------------------------------------------
|
| Everything `php artisan tenant:server-sync` writes on the server. It runs
| as ROOT (from the cron file it installs with --shared), never from a web
| request: the web user cannot and should not touch /etc.
|
| With a wildcard nginx site and wildcard certificate (wildcard_site=true),
| subdomain tenants need nothing here and only CUSTOM domains get their own
| nginx site and Let's Encrypt certificate. Without one, a tenant's pointed
| subdomain (orga.example.com) gets its own site and certificate too, the
| same way. The scheduler and queue workers are shared by every tenant, so
| they are installed once (--shared).
|
*/

return [

    'name' => env('TENANT_SERVER_NAME', 'qloud'),

    'app_path' => env('TENANT_SERVER_APP_PATH', base_path()),

    'php_binary' => env('TENANT_SERVER_PHP', '/usr/bin/php'),

    'php_fpm_socket' => env('TENANT_SERVER_PHP_FPM_SOCKET', '/run/php/php8.4-fpm.sock'),

    /* A wildcard site + certificate already serves every tenant subdomain. */
    'wildcard_site' => env('TENANT_SERVER_WILDCARD', true),

    'web_user' => env('TENANT_SERVER_WEB_USER', 'www-data'),

    /* Refuse to write /etc unless running as root. Tests switch this off. */
    'require_root' => env('TENANT_SERVER_REQUIRE_ROOT', true),

    'nginx' => [
        'sites_available' => env('TENANT_SERVER_NGINX_AVAILABLE', '/etc/nginx/sites-available'),
        'sites_enabled' => env('TENANT_SERVER_NGINX_ENABLED', '/etc/nginx/sites-enabled'),
        'test_command' => ['nginx', '-t'],
        'reload_command' => ['systemctl', 'reload', 'nginx'],
        'client_max_body_size' => env('TENANT_SERVER_MAX_BODY', '64M'),
    ],

    'certbot' => [
        'binary' => env('TENANT_SERVER_CERTBOT', 'certbot'),
        'email' => env('TENANT_SERVER_CERTBOT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'live_path' => env('TENANT_SERVER_CERT_PATH', '/etc/letsencrypt/live'),
    ],

    'supervisor' => [
        'conf_dir' => env('TENANT_SERVER_SUPERVISOR_DIR', '/etc/supervisor/conf.d'),
        'processes' => (int) env('TENANT_SERVER_QUEUE_PROCESSES', 2),
        'reload_commands' => [['supervisorctl', 'reread'], ['supervisorctl', 'update']],
    ],

    'cron_file' => env('TENANT_SERVER_CRON_FILE', '/etc/cron.d/qloud'),

];
