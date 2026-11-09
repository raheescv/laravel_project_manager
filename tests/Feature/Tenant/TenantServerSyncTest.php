<?php

use App\Http\Middleware\IdentifyTenant;
use App\Livewire\Tenant\Page;
use App\Livewire\Tenant\View;
use App\Models\Tenant;
use App\Services\TenantServerService;
use App\Services\TenantService;
use Illuminate\Http\Request;
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

/** @param  list<string>  $aliases */
function tenantWithAliases(array $aliases, array $attributes = []): Tenant
{
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => null, ...$attributes]);
    $tenant->domain_aliases = $aliases;
    $tenant->save();

    return $tenant;
}

function superAdminOnServerTab(Tenant $tenant): \Livewire\Features\SupportTesting\Testable
{
    $world = PosWorld::create();
    $world->user->forceFill(['is_super_admin' => true])->save();

    return Livewire::actingAs($world->user)->test(View::class, ['tenantId' => $tenant->id])->call('selectTab', 'server');
}

it('opens the same tenant from its subdomain and an alias under the same domain', function (): void {
    config(['app.url' => 'https://app.astraqatar.com', 'tenant_server.wildcard_site' => true]);
    $tenant = tenantWithAliases(['newrest.astraqatar.com'], ['subdomain' => 'spa']);
    $resolve = function (string $url): ?int {
        $service = new TenantService();
        (new IdentifyTenant($service))->handle(Request::create($url), fn () => response('ok'));

        return $service->getCurrentTenantId();
    };

    expect($resolve('https://spa.astraqatar.com/'))->toBe($tenant->id)
        ->and($resolve('https://newrest.astraqatar.com/'))->toBe($tenant->id)
        ->and($tenant->fresh()->needsServerSite())->toBeFalse()
        ->and($tenant->fresh()->domain_status)->toBeNull();
});

it('drops a remembered miss as soon as the alias is added, and the hit once removed', function (): void {
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => null]);
    $service = app(TenantService::class);
    expect($service->findTenantByDomain('shop.acme.com'))->toBeNull();

    $tenant->domain_aliases = ['shop.acme.com'];
    $tenant->save();
    expect($service->findTenantByDomain('shop.acme.com')?->id)->toBe($tenant->id);

    $tenant->domain_aliases = null;
    $tenant->save();
    expect($service->findTenantByDomain('shop.acme.com'))->toBeNull();
});

it('does not resolve an alias of a deactivated tenant', function (): void {
    tenantWithAliases(['shop.acme.com'], ['is_active' => false]);

    expect(app(TenantService::class)->findTenantByDomain('shop.acme.com'))->toBeNull();
});

it('serves the domain and its aliases from one site and one certificate', function (): void {
    fakeServer($this->etc);
    $tenant = tenantWithAliases(['other.acme.com'], ['domain' => 'shop.acme.com']);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect(File::get("{$this->etc}/available/acme"))
        ->toContain('server_name shop.acme.com other.acme.com;')
        ->toContain("ssl_certificate {$this->etc}/live/shop.acme.com/fullchain.pem;")
        ->and($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_ACTIVE);

    Process::assertRan(fn ($process) => str_contains(implode(' ', (array) $process->command), '--cert-name shop.acme.com -d shop.acme.com -d other.acme.com'));
});

it('gives a tenant with only an outside alias its own site', function (): void {
    fakeServer($this->etc);
    $tenant = tenantWithAliases(['client.example.com']);
    expect($tenant->domain_status)->toBe(Tenant::DOMAIN_PENDING);

    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect(File::get("{$this->etc}/available/acme"))->toContain('server_name client.example.com;')
        ->and($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_ACTIVE);
});

it('keeps an outside alias in the site when the custom domain is removed', function (): void {
    fakeServer($this->etc);
    $tenant = tenantWithAliases(['client.example.com'], ['domain' => 'shop.acme.com']);
    $this->artisan('tenant:server-sync')->assertSuccessful();

    $tenant->refresh()->update(['domain' => null]);
    expect($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_PENDING);
    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect(File::get("{$this->etc}/available/acme"))->toContain('server_name client.example.com;');
});

it('expands an existing certificate when an alias is added', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);
    $this->artisan('tenant:server-sync')->assertSuccessful();

    $tenant->refresh();
    $tenant->domain_aliases = ['other.acme.com'];
    $tenant->save();
    $this->artisan('tenant:server-sync')->assertSuccessful();

    Process::assertRan(fn ($process) => str_contains(implode(' ', (array) $process->command), '-d shop.acme.com -d other.acme.com'));
    expect($tenant->fresh()->domain_status)->toBe(Tenant::DOMAIN_ACTIVE);
});

it('keeps HTTPS and names every host when expanding the certificate fails', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => 'shop.acme.com']);
    $this->artisan('tenant:server-sync')->assertSuccessful();

    Process::swap(new \Illuminate\Process\Factory());
    fakeServer($this->etc, certbotSucceeds: false);
    $tenant->refresh();
    $tenant->domain_aliases = ['other.acme.com'];
    $tenant->save();
    $this->artisan('tenant:server-sync')->assertSuccessful();

    expect($tenant->fresh())->domain_status->toBe(Tenant::DOMAIN_FAILED)
        ->domain_error->toContain('shop.acme.com, other.acme.com have DNS A records')
        ->domain_error->toContain('existing certificate stays in use')
        ->and(File::get("{$this->etc}/available/acme"))->toContain('listen 443 ssl');
});

it('adds and removes aliases from the Server tab', function (): void {
    fakeServer($this->etc);
    $tenant = Tenant::factory()->create(['subdomain' => 'orga', 'domain' => null]);

    $component = superAdminOnServerTab($tenant)
        ->set('aliasDomain', 'https://NewRest.test/')
        ->call('addDomainAlias')
        ->assertHasNoErrors()
        ->assertSet('aliasDomain', '')
        ->assertSee('newrest.test')
        ->assertSee('Served by the wildcard site')
        ->set('aliasDomain', 'shop.orga.com')
        ->call('addDomainAlias')
        ->assertHasNoErrors()
        ->assertSee('Waiting for the server');

    expect($tenant->fresh()->aliasHosts())->toBe(['newrest.test', 'shop.orga.com']);

    $this->artisan('tenant:server-sync')->assertSuccessful();
    expect(File::get("{$this->etc}/available/orga"))->toContain('server_name shop.orga.com;');

    $component->call('removeDomainAlias', 'shop.orga.com')->call('removeDomainAlias', 'newrest.test')->assertDispatched('success');
    expect($tenant->fresh())->domain_aliases->toBeNull();

    $this->artisan('tenant:server-sync')->assertSuccessful();
    expect(File::exists("{$this->etc}/available/orga"))->toBeFalse();
});

it('refuses an alias that already opens this tenant or belongs to another', function (string $alias, array|string $error): void {
    tenantWithAliases(['taken.acme.com'], ['subdomain' => 'other', 'domain' => 'owned.acme.com']);
    $tenant = tenantWithAliases(['mine.orga.com'], ['subdomain' => 'orga', 'domain' => 'shop.orga.com']);

    superAdminOnServerTab($tenant)->set('aliasDomain', $alias)->call('addDomainAlias')->assertHasErrors($error);

    expect($tenant->fresh()->aliasHosts())->toBe(['mine.orga.com']);
})->with([
    'the app address' => ['app.test', ['aliasDomain' => 'not_in']],
    'its own subdomain' => ['orga.test', 'aliasDomain'],
    'its own domain' => ['shop.orga.com', 'aliasDomain'],
    'its own alias' => ['mine.orga.com', 'aliasDomain'],
    'another domain' => ['owned.acme.com', 'aliasDomain'],
    'another alias' => ['taken.acme.com', 'aliasDomain'],
    'another subdomain address' => ['other.test', 'aliasDomain'],
    'not a host name' => ['not a host', ['aliasDomain' => 'regex']],
]);

function superAdminOnEditForm(Tenant $tenant): \Livewire\Features\SupportTesting\Testable
{
    $world = PosWorld::create();
    $world->user->forceFill(['is_super_admin' => true])->save();

    return Livewire::actingAs($world->user)->test(Page::class, ['table_id' => $tenant->id]);
}

it('saves, shows and clears aliases from the Edit tenant form', function (): void {
    $tenant = Tenant::factory()->create(['subdomain' => 'orga', 'domain' => null]);

    superAdminOnEditForm($tenant)
        ->assertSet('aliasDomains', '')
        ->assertSee('Alias domains')
        ->set('aliasDomains', 'https://NewRest.test/, shop.orga.com newrest.test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('aliasDomains', 'newrest.test, shop.orga.com');

    expect($tenant->fresh())->aliasHosts()->toBe(['newrest.test', 'shop.orga.com'])
        ->domain_status->toBe(Tenant::DOMAIN_PENDING);

    superAdminOnEditForm($tenant->fresh())
        ->assertSet('aliasDomains', 'newrest.test, shop.orga.com')
        ->set('aliasDomains', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($tenant->fresh()->domain_aliases)->toBeNull();
});

it('keeps saved aliases when the form changes something else', function (): void {
    $tenant = tenantWithAliases(['shop.orga.com'], ['subdomain' => 'orga']);
    Tenant::whereKey($tenant->id)->update(['domain_status' => Tenant::DOMAIN_ACTIVE]);

    superAdminOnEditForm($tenant->fresh())->set('tenants.name', 'Renamed')->call('save')->assertHasNoErrors();

    expect($tenant->fresh())->name->toBe('Renamed')
        ->aliasHosts()->toBe(['shop.orga.com'])
        ->domain_status->toBe(Tenant::DOMAIN_ACTIVE);
});

it('refuses a bad alias on the Edit tenant form', function (string $alias): void {
    tenantWithAliases(['taken.acme.com'], ['subdomain' => 'other', 'domain' => 'owned.acme.com']);
    $tenant = Tenant::factory()->create(['subdomain' => 'orga', 'domain' => 'shop.orga.com']);

    superAdminOnEditForm($tenant)->set('aliasDomains', $alias)->call('save')->assertHasErrors('tenants.domain_aliases.0');

    expect($tenant->fresh()->domain_aliases)->toBeNull();
})->with([
    'the app address' => ['app.test'],
    'its own subdomain' => ['orga.test'],
    'its own domain' => ['shop.orga.com'],
    'another domain' => ['owned.acme.com'],
    'another alias' => ['taken.acme.com'],
    'another subdomain address' => ['other.test'],
    'not a host name' => ['nothost'],
]);

it('stops a domain or subdomain from taking over another tenant alias', function (): void {
    tenantWithAliases(['shop.acme.com', 'newrest.test']);

    expect(Validator::make(['domain' => 'shop.acme.com'], ['domain' => Tenant::rules()['domain']])->fails())->toBeTrue()
        ->and(Validator::make(['subdomain' => 'newrest'], ['subdomain' => Tenant::rules()['subdomain']])->fails())->toBeTrue()
        ->and(Validator::make(['subdomain' => 'fresh'], ['subdomain' => Tenant::rules()['subdomain']])->fails())->toBeFalse();
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
