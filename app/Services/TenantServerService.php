<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Renders and applies the server side of Tenant Control.
 *
 * - Custom domains: one nginx site per tenant (HTTP first so Let's Encrypt can
 *   answer the webroot challenge, then HTTPS once the certificate exists).
 * - Shared, installed once: the queue worker Supervisor program and the cron
 *   file (Laravel scheduler as the web user + this sync as root).
 *
 * Every write is checked with `nginx -t` and rolled back when rejected, so a
 * bad domain can never take the other tenants' sites down with it.
 * Only ever called from the root-run tenant:server-sync command.
 */
class TenantServerService
{
    public const HEARTBEAT_KEY = 'tenant_server:scheduler_heartbeat';

    /**
     * @return array{status: string, error: ?string}
     */
    public function syncTenant(Tenant $tenant): array
    {
        $domain = $tenant->domain;
        $hasCertificate = $this->certificateExists($domain);

        $error = $this->writeSite($tenant, $hasCertificate);
        if ($error) {
            return ['status' => Tenant::DOMAIN_FAILED, 'error' => $error];
        }
        if ($hasCertificate) {
            return ['status' => Tenant::DOMAIN_ACTIVE, 'error' => null];
        }

        $certbot = $this->run($this->certbotCommand($domain));
        if (! $certbot->successful()) {
            return [
                'status' => Tenant::DOMAIN_FAILED,
                'error' => "Certificate not issued — check that {$domain} has a DNS A record pointing to this server. The site is live over HTTP meanwhile.\n".$this->tail($certbot),
            ];
        }

        $error = $this->writeSite($tenant, true);

        return $error
            ? ['status' => Tenant::DOMAIN_FAILED, 'error' => $error]
            : ['status' => Tenant::DOMAIN_ACTIVE, 'error' => null];
    }

    /**
     * Take a tenant's nginx site down (domain removed, tenant deactivated or
     * deleted). The certificate is left for certbot to expire on its own.
     */
    public function removeSite(int $tenantId): bool
    {
        $available = $this->sitePath($tenantId);
        $enabled = $this->enabledPath($tenantId);
        if (! File::exists($available) && ! is_link($enabled) && ! File::exists($enabled)) {
            return false;
        }

        File::delete([$enabled, $available]);
        if ($this->run(config('tenant_server.nginx.test_command'))->successful()) {
            $this->run(config('tenant_server.nginx.reload_command'));
        }

        return true;
    }

    /**
     * Tenant ids that currently have a managed nginx site on disk.
     *
     * @return list<int>
     */
    public function managedSiteIds(): array
    {
        $pattern = config('tenant_server.nginx.sites_available').'/'.config('tenant_server.name').'-tenant-*.conf';

        return collect(File::glob($pattern))
            ->map(fn (string $path): int => (int) preg_replace('/\D/', '', basename($path, '.conf')))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Write (or overwrite) the queue worker program and the cron file.
     *
     * @return list<string> what was done, for the command output
     */
    public function installShared(): array
    {
        $done = [];

        File::put($this->supervisorPath(), $this->renderSupervisor());
        $done[] = 'Supervisor program written: '.$this->supervisorPath();
        foreach (config('tenant_server.supervisor.reload_commands') as $command) {
            $result = $this->run($command);
            $done[] = implode(' ', $command).($result->successful() ? ' ✓' : ' ✗ '.$this->tail($result));
        }

        // cron.d files must be owned by root and not group/world writable.
        File::put(config('tenant_server.cron_file'), $this->renderCron());
        @chmod(config('tenant_server.cron_file'), 0644);
        $done[] = 'Cron file written: '.config('tenant_server.cron_file');

        return $done;
    }

    public function renderSite(Tenant $tenant, bool $withSsl): string
    {
        $domain = $tenant->domain;
        $root = rtrim(config('tenant_server.app_path'), '/').'/public';
        $live = rtrim(config('tenant_server.certbot.live_path'), '/').'/'.$domain;
        $app = $this->appLocations();
        $header = "# Managed by Tenant Control — tenant #{$tenant->id} ({$tenant->code}).\n# Do not edit: it is rewritten by `php artisan tenant:server-sync`.\n";

        $challenge = "    location ^~ /.well-known/acme-challenge/ {\n        root {$root};\n        default_type \"text/plain\";\n    }\n";

        if (! $withSsl) {
            return $header.<<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};
    root {$root};

{$challenge}
{$app}
}

NGINX;
        }

        return $header.<<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};

{$challenge}
    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$domain};
    root {$root};

    ssl_certificate {$live}/fullchain.pem;
    ssl_certificate_key {$live}/privkey.pem;

{$app}
}

NGINX;
    }

    public function renderSupervisor(): string
    {
        $name = config('tenant_server.name');
        $path = rtrim(config('tenant_server.app_path'), '/');
        $php = config('tenant_server.php_binary');
        $user = config('tenant_server.web_user');
        $processes = max(1, (int) config('tenant_server.supervisor.processes'));
        $command = $this->queueCommand();
        $processLine = $command === 'horizon' ? 'numprocs=1' : "numprocs={$processes}";

        return <<<INI
; Managed by Tenant Control — queue workers shared by every tenant.
; Rewritten by `php artisan tenant:server-sync --shared`.
[program:{$name}-queue]
process_name=%(program_name)s_%(process_num)02d
command={$php} {$path}/artisan {$command}
directory={$path}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user={$user}
{$processLine}
redirect_stderr=true
stdout_logfile={$path}/storage/logs/queue-worker.log
stopwaitsecs=3600

INI;
    }

    public function renderCron(): string
    {
        $path = rtrim(config('tenant_server.app_path'), '/');
        $php = config('tenant_server.php_binary');
        $user = config('tenant_server.web_user');

        return <<<CRON
# Managed by Tenant Control — rewritten by `php artisan tenant:server-sync --shared`.
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Laravel scheduler for every tenant (runs as the web user).
* * * * * {$user} cd {$path} && {$php} artisan schedule:run >> /dev/null 2>&1

# Custom-domain nginx sites and certificates (needs root).
* * * * * root cd {$path} && {$php} artisan tenant:server-sync >> /var/log/tenant-server-sync.log 2>&1

CRON;
    }

    /**
     * Horizon when the queue runs on Redis and Horizon is installed; a plain
     * worker otherwise (this app's default is the database queue).
     */
    public function queueCommand(): string
    {
        $connection = config('queue.default');
        if (config("queue.connections.{$connection}.driver") === 'redis' && class_exists(\Laravel\Horizon\Horizon::class)) {
            return 'horizon';
        }

        return "queue:work {$connection} --sleep=3 --tries=3 --max-time=3600";
    }

    /**
     * Shared platform health for the Tenant Control Server tab.
     *
     * @return array{scheduler_last_run: ?string, queue_driver: string, pending_jobs: ?int, failed_jobs: ?int, oldest_job: ?string}
     */
    public function health(): array
    {
        $connection = config('queue.default');
        $driver = (string) config("queue.connections.{$connection}.driver");
        $pending = $failed = null;
        $oldest = null;

        try {
            if ($driver === 'database') {
                $jobs = DB::table(config("queue.connections.{$connection}.table", 'jobs'));
                $pending = (clone $jobs)->count();
                $oldestAt = (clone $jobs)->min('created_at');
                $oldest = $oldestAt ? Carbon::createFromTimestamp($oldestAt)->toDateTimeString() : null;
            }
            $failed = DB::table(config('queue.failed.table', 'failed_jobs'))->count();
        } catch (\Throwable) {
            // Missing queue tables are reported as unknown, not as an error page.
        }

        $heartbeat = Cache::get(self::HEARTBEAT_KEY);

        return [
            'scheduler_last_run' => $heartbeat ? Carbon::parse($heartbeat)->toDateTimeString() : null,
            'queue_driver' => $driver ?: 'sync',
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'oldest_job' => $oldest,
        ];
    }

    public function sitePath(int $tenantId): string
    {
        return config('tenant_server.nginx.sites_available').'/'.config('tenant_server.name')."-tenant-{$tenantId}.conf";
    }

    public function enabledPath(int $tenantId): string
    {
        return config('tenant_server.nginx.sites_enabled').'/'.config('tenant_server.name')."-tenant-{$tenantId}.conf";
    }

    public function supervisorPath(): string
    {
        return config('tenant_server.supervisor.conf_dir').'/'.config('tenant_server.name').'-queue.conf';
    }

    public function certificateExists(string $domain): bool
    {
        return File::exists(rtrim(config('tenant_server.certbot.live_path'), '/')."/{$domain}/fullchain.pem");
    }

    /**
     * Write the site, enable it and reload nginx — or put the previous version
     * back when `nginx -t` rejects it. Returns the error, or null on success.
     */
    private function writeSite(Tenant $tenant, bool $withSsl): ?string
    {
        $available = $this->sitePath($tenant->id);
        $enabled = $this->enabledPath($tenant->id);
        $previous = File::exists($available) ? File::get($available) : null;

        File::put($available, $this->renderSite($tenant, $withSsl));
        if (! is_link($enabled) && ! File::exists($enabled)) {
            File::link($available, $enabled);
        }

        $test = $this->run(config('tenant_server.nginx.test_command'));
        if (! $test->successful()) {
            if ($previous === null) {
                File::delete([$enabled, $available]);
            } else {
                File::put($available, $previous);
            }

            return "nginx rejected the site, previous configuration kept.\n".$this->tail($test);
        }

        $reload = $this->run(config('tenant_server.nginx.reload_command'));

        return $reload->successful() ? null : "nginx did not reload.\n".$this->tail($reload);
    }

    /**
     * @return list<string>
     */
    private function certbotCommand(string $domain): array
    {
        $email = config('tenant_server.certbot.email');

        return [
            config('tenant_server.certbot.binary'), 'certonly', '--webroot',
            '-w', rtrim(config('tenant_server.app_path'), '/').'/public',
            '-d', $domain,
            '--non-interactive', '--agree-tos', '--keep-until-expiring',
            ...($email ? ['-m', $email] : ['--register-unsafely-without-email']),
        ];
    }

    private function appLocations(): string
    {
        $socket = config('tenant_server.php_fpm_socket');
        $maxBody = config('tenant_server.nginx.client_max_body_size');

        return <<<NGINX
    index index.php;
    charset utf-8;
    client_max_body_size {$maxBody};

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:{$socket};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
NGINX;
    }

    private function run(array $command): ProcessResult
    {
        return Process::timeout(180)->run($command);
    }

    private function tail(ProcessResult $result): string
    {
        return trim(mb_substr(trim($result->errorOutput()."\n".$result->output()), -800));
    }
}
