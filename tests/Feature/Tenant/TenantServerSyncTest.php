<?php

use App\Livewire\Tenant\View;
use App\Models\Tenant;
use App\Services\TenantServerService;
use App\Services\TenantService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * tenant:server-sync against a throw-away /etc: nginx, certbot and
 * supervisorctl are faked, the files are real.
 */
beforeEach(function (): void {
    $this->etc = sys_get_temp_dir().'/tenant-server-'.uniqid();
    foreach (['available', 'enabled', 'live', 'supervisor'] as $dir) {
        File::ensureDirectoryExists("{$this->etc}/{$dir}");
    }

    config([
        'app.url' => 'https://app.test',
        'tenant_server.require_root' => false,
        'tenant_server.app_path' => '/var/www/qloud',
        'tenant_server.nginx.sites_available' => "{$this->etc}/available",
        'tenant_server.nginx.sites_enabled' => "{$this->etc}/enabled",
        'tenant_server.certbot.live_path' => "{$this->etc}/live",
        'tenant_server.certbot.email' => 'ops@example.com',
        'tenant_server.supervisor.conf_dir' => "{$this->etc}/supervisor",
        'tenant_server.cron_file' => "{$this->etc}/cron-qloud",
    ]);
});

afterEach(function (): void {
    File::deleteDirectory($this->etc);
});

/**
 * Fake every server binary (a catch-all, so nothing real ever runs); certbot
 * "issues" a certificate unless told to fail.
 */
function fakeServer(string $etc, bool $certbotSucceeds = true, bool $nginxAccepts = true): void
{
    Process::fake(function ($process) use ($etc, $certbotSucceeds, $nginxAccepts) {
        $command = implode(' ', (array) $process->command);

        if ($command === 'nginx -t') {
            return $nginxAccepts ? Process::result('syntax is ok') : Process::result(errorOutput: 'unknown directive', exitCode: 1);
        }
        if (str_starts_with($command, 'certbot ')) {
            if (! $certbotSucceeds) {
                return Process::result(errorOutput: 'DNS problem: NXDOMAIN looking up A', exitCode: 1);
            }
            preg_match('/-d (\S+)/', $command, $match);
            File::ensureDirectoryExists("{$etc}/live/{$match[1]}");
            File::put("{$etc}/live/{$match[1]}/fullchain.pem", 'cert');
        }

        return Process::result();
    });
}

/** @param  list<string>  $command */
function ranCommand(string $command): Closure
{
    return fn ($process) => implode(' ', (array) $process->command) === $command;
}

it('marks a custom domain for sync and normalises what was typed', function (): void {
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'https://Shop.Acme.com/']);

    expect($tenant->domain)->toBe('shop.acme.com')
        ->and($tenant->hasCustomDomain())->toBeTrue()
        ->and($tenant->domain_status)->toBe(Tenant::DOMAIN_PENDING);

    $tenant->update(['domain' => null]);
    expect($tenant->fresh()->domain_status)->toBeNull();

    $plain = Tenant::factory()->create(['subdomain' => 'beta', 'domain' => 'beta.test']);
    expect($plain->hasCustomDomain())->toBeFalse()->and($plain->domain_status)->toBeNull();
});

it('refuses the app address and a domain another tenant owns', function (): void {
    Tenant::factory()->create(['domain' => 'shop.acme.com']);

    expect(Validator::make(['domain' => 'app.test'], ['domain' => Tenant::rules()['domain']])->fails())->toBeTrue()
        ->and(Validator::make(['domain' => 'shop.acme.com'], ['domain' => Tenant::rules()['domain']])->fails())->toBeTrue()
        ->and(Validator::make(['domain' => 'new.acme.com'], ['domain' => Tenant::rules()['domain']])->fails())->toBeFalse();
});

it('resolves a tenant by its custom domain but never by the app host', function (): void {
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);
    $service = app(TenantService::class);

    expect($service->findTenantByDomain('shop.acme.com')?->id)->toBe($tenant->id)
        ->and($service->findTenantByDomain('app.test'))->toBeNull()
        ->and($service->findTenantByDomain('unknown.example.com'))->toBeNull();
});

it('writes the site, issues the certificate and switches to HTTPS', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    $site = File::get("{$this->etc}/available/acme");
    expect($site)->toContain('server_name shop.acme.com;')
        ->toContain('listen 443 ssl')
        ->toContain('/var/www/qloud/public')
        ->toContain('/.well-known/acme-challenge/')
        ->toStartWith(TenantServerService::SITE_MARKER.$tenant->id)
        ->toContain('ssl_protocols TLSv1.2 TLSv1.3;')
        ->toContain('add_header Referrer-Policy strict-origin-when-cross-origin always;')
        ->toContain('fastcgi_read_timeout 180;')
        ->toContain('location ~* ^/(build|storage)/')
        ->toContain('access_log /var/log/nginx/acme.access.log;')
        ->and(is_link("{$this->etc}/enabled/acme"))->toBeTrue()
        ->and($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_ACTIVE);

    Process::assertRan(fn ($process) => str_contains(implode(' ', (array) $process->command), 'certonly --webroot'));
});

it('keeps the site on HTTP and records why when the certificate fails', function (): void {
    fakeServer($this->etc, certbotSucceeds: false);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    $tenant->refresh();
    expect($tenant->domain_status)->toBe(Tenant::DOMAIN_FAILED)
        ->and($tenant->domain_error)->toContain('DNS A record')
        ->and(File::get("{$this->etc}/available/acme"))->not->toContain('listen 443');

    // Failed tenants are not retried every minute (Let's Encrypt rate limits) unless asked.
    Process::swap(new \Illuminate\Process\Factory());
    Process::fake(fn () => Process::result());
    $this->artisan('tenant:server-sync')->assertSuccessful();
    Process::assertNothingRan();
});

it('rolls a rejected site back so nginx keeps serving everyone else', function (): void {
    fakeServer($this->etc, nginxAccepts: false);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect(File::exists("{$this->etc}/available/acme"))->toBeFalse()
        ->and($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_FAILED);
    Process::assertNotRan(ranCommand('systemctl reload nginx'));
});

it('takes the site down when the tenant is deactivated or deleted', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);
    $this->artisan('tenant:server-sync')->assertSuccessful();
    $site = "{$this->etc}/available/acme";
    expect(File::exists($site))->toBeTrue();

    $tenant->refresh()->update(['is_active' => false]);
    $this->artisan('tenant:server-sync')->assertSuccessful();
    expect(File::exists($site))->toBeFalse();

    $tenant->refresh()->update(['is_active' => true]);
    $this->artisan('tenant:server-sync')->assertSuccessful();
    expect(File::exists($site))->toBeTrue();

    $tenant->refresh()->delete();
    $this->artisan('tenant:server-sync')->assertSuccessful();
    expect(File::exists($site))->toBeFalse()
        ->and(is_link("{$this->etc}/enabled/acme"))->toBeFalse();
});

it('installs the shared queue worker and cron file', function (): void {
    fakeServer($this->etc);
    config(['queue.default' => 'database']);

    $this->artisan('tenant:server-sync --shared')->assertSuccessful();

    expect(File::get("{$this->etc}/supervisor/qloud-queue.conf"))
        ->toContain('[program:qloud-queue]')
        ->toContain('artisan queue:work database')
        ->toContain('user=www-data')
        ->and(File::get("{$this->etc}/cron-qloud"))
        ->toContain('www-data cd /var/www/qloud && /usr/bin/php artisan schedule:run')
        ->toContain('root cd /var/www/qloud && /usr/bin/php artisan tenant:server-sync');
    Process::assertRan(ranCommand('supervisorctl reread'));
    Process::assertRan(ranCommand('supervisorctl update'));
});

it('previews without touching the server on a dry run', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);

    $this->artisan('tenant:server-sync --shared --dry-run')
        ->expectsOutputToContain('server_name shop.acme.com;')
        ->assertSuccessful();

    Process::assertNothingRan();
    expect(File::files("{$this->etc}/available"))->toBeEmpty()
        ->and($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_PENDING);
});

it('refuses to run without root', function (): void {
    config(['tenant_server.require_root' => true]);

    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
        $this->markTestSkipped('The suite is running as root.');
    }

    $this->artisan('tenant:server-sync')->expectsOutputToContain('Run this as root')->assertFailed();
});

it('sets up a pointed subdomain from the Server tab when there is no wildcard site', function (): void {
    fakeServer($this->etc);
    config(['tenant_server.wildcard_site' => false]);
    $world = PosWorld::create();
    $world->user->forceFill(['is_super_admin' => true])->save();
    $tenant = Tenant::factory()->create(['subdomain' => 'orga', 'domain' => null]);

    Livewire::actingAs($world->user)->test(View::class, ['tenantId' => $tenant->id])
        ->call('selectTab', 'server')
        ->assertSee('Use the subdomain')
        ->set('customDomain', 'orga'.Tenant::subdomainSuffix())
        ->call('saveCustomDomain')
        ->assertHasNoErrors()
        ->assertDispatched('success')
        ->assertSet('customDomain', '')
        ->assertSee('Waiting for the server');

    expect($tenant->fresh())->domain->toBe('orga.test')->domain_status->toBe(Tenant::DOMAIN_PENDING);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_ACTIVE)
        ->and(File::get("{$this->etc}/available/orga"))
        ->toContain('server_name orga.test;')
        ->toContain("ssl_certificate {$this->etc}/live/orga.test/fullchain.pem;");
});

it('refuses the subdomain address when a wildcard site already serves it', function (): void {
    config(['tenant_server.wildcard_site' => true]);
    $world = PosWorld::create();
    $world->user->forceFill(['is_super_admin' => true])->save();
    $tenant = Tenant::factory()->create(['subdomain' => 'orga', 'domain' => null]);

    Livewire::actingAs($world->user)->test(View::class, ['tenantId' => $tenant->id])
        ->call('selectTab', 'server')
        ->assertSee('shared wildcard site')
        ->assertDontSee('Use the subdomain')
        ->set('customDomain', 'orga.test')->call('saveCustomDomain')->assertHasErrors('customDomain')
        ->set('customDomain', 'app.test')->call('saveCustomDomain')->assertHasErrors(['customDomain' => 'not_in'])
        ->set('customDomain', '')->call('saveCustomDomain')->assertHasErrors(['customDomain' => 'required'])
        ->set('customDomain', 'https://Shop.Orga.com/')->call('saveCustomDomain')->assertHasNoErrors();

    expect($tenant->fresh()->domain)->toBe('shop.orga.com');
});

it('removes the domain and its nginx site from the Server tab', function (): void {
    fakeServer($this->etc);
    $world = PosWorld::create();
    $world->user->forceFill(['is_super_admin' => true])->save();
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);
    $this->artisan('tenant:server-sync')->assertSuccessful();
    $site = "{$this->etc}/available/acme";
    expect(File::exists($site))->toBeTrue();

    Livewire::actingAs($world->user)->test(View::class, ['tenantId' => $tenant->id])
        ->call('selectTab', 'server')
        ->call('removeCustomDomain')
        ->assertDispatched('success');

    expect($tenant->fresh()->domain)->toBeNull();

    $this->artisan('tenant:server-sync')->assertSuccessful();
    expect(File::exists($site))->toBeFalse();
});

it('never overwrites a hand-written site with the same name', function (): void {
    fakeServer($this->etc);
    File::put("{$this->etc}/available/solan", "# hand-written\n");
    $tenant = Tenant::factory()->create(['subdomain' => 'solan', 'domain' => 'shop.solan.com']);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect(File::get("{$this->etc}/available/solan"))->toBe("# hand-written\n")
        ->and($tenant->fresh())->domain_status->toBe(Tenant::DOMAIN_FAILED)
        ->domain_error->toContain('not written by Tenant Control');
});

it('renames the site when the subdomain changes', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);
    $this->artisan('tenant:server-sync')->assertSuccessful();

    $tenant->refresh()->update(['subdomain' => 'acme-retail']);
    expect($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_PENDING);
    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect(File::exists("{$this->etc}/available/acme-retail"))->toBeTrue()
        ->and(is_link("{$this->etc}/enabled/acme-retail"))->toBeTrue()
        ->and(File::exists("{$this->etc}/available/acme"))->toBeFalse()
        ->and(is_link("{$this->etc}/enabled/acme"))->toBeFalse();
});
