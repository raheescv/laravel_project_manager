<?php

use App\Livewire\Tenant\View;
use App\Models\Tenant;
use App\Services\ServerActionService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Deploy buttons on the Server tab. The launch is faked, so nothing runs; the
 * test then writes what the detached shell would have appended to the log.
 */
beforeEach(function (): void {
    $this->storage = sys_get_temp_dir().'/server-actions-'.uniqid();
    File::ensureDirectoryExists($this->storage);
    app()->useStoragePath($this->storage);

    config([
        'tenant_server.app_path' => '/var/www/qloud',
        'tenant_server.php_binary' => '/usr/bin/php',
        'tenant_server.npm_binary' => 'npm',
        'tenant_server.web_user' => 'www-data',
    ]);
    Process::fake();

    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->tenant = Tenant::factory()->create(['subdomain' => 'acme', 'domain' => null]);
});

afterEach(function (): void {
    File::deleteDirectory($this->storage);
});

function finishServerAction(int $exitCode, string $output = ''): void
{
    File::append(app(ServerActionService::class)->logPath(), $output.ServerActionService::EXIT_MARKER.$exitCode."\n");
}

it('launches each deploy command detached from the Server tab', function (string $action, string $command): void {
    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('selectTab', 'server')
        ->call('runServerAction', $action)
        ->assertDispatched('success')
        ->assertSee('Running');

    Process::assertRan(fn ($process) => $process->path === '/var/www/qloud'
        && $process->command[0] === 'sh'
        && str_starts_with($process->command[2], 'nohup sh -c')
        && str_contains($process->command[2], "/var/www/qloud'\\'' && {$command}; echo")
        && str_ends_with($process->command[2], '2>&1 < /dev/null &'));
})->with([
    'git pull' => ['pull', 'git pull --ff-only'],
    'npm run build' => ['build', 'npm run build'],
    'migrate' => ['migrate', '/usr/bin/php artisan migrate --force'],
    'server setup' => ['setup', 'sudo -n /usr/bin/php /var/www/qloud/artisan tenant:server-sync --shared'],
]);

it('runs one action at a time and shows its output once it ends', function (): void {
    $component = Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('selectTab', 'server')
        ->call('runServerAction', 'pull')
        ->call('runServerAction', 'migrate')
        ->assertDispatched('error', ['message' => 'Git pull is still running — wait for it to finish']);

    Process::assertRanTimes(fn ($process) => $process->command[0] === 'sh', 1);

    finishServerAction(0, "Fast-forward\n \e[32mapp/Models/Tenant.php\e[0m | 2 +-\n");

    $component->call('$refresh')
        ->assertSee('Done')
        ->assertSee('$ git pull --ff-only')
        ->assertSee('app/Models/Tenant.php | 2 +-')
        ->assertDontSee(ServerActionService::EXIT_MARKER)
        ->call('runServerAction', 'migrate')
        ->assertDispatched('success', ['message' => 'Migrate started']);
});

it('shows the sudoers line when the web user may not run the setup as root', function (): void {
    $component = Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('selectTab', 'server')
        ->call('runServerAction', 'setup');

    finishServerAction(1, "sudo: a password is required\n");

    $component->call('$refresh')
        ->assertSee('Failed · exit 1')
        ->assertSee('www-data ALL=(root) NOPASSWD: /usr/bin/php /var/www/qloud/artisan tenant:server-sync --shared');
});

it('shows how many commits Git pull would bring in and checks for more', function (): void {
    Process::fake(function ($process) {
        return match (array_slice((array) $process->command, 0, 2)) {
            ['git', 'rev-parse'] => Process::result("main\norigin/main\n"),
            ['git', 'log'] => Process::result("abc1234\tFix the POS total\tRahees\t2 hours ago\ndef5678\tAdd stock report\tAisha\t1 day ago\n"),
            default => Process::result(),
        };
    });

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('selectTab', 'server')
        ->assertSee('main ← origin/main')
        ->assertSee('2 commits to pull')
        ->assertSee('Fix the POS total')
        ->assertSee('Aisha, 1 day ago')
        ->assertSee('Pull 2 commits onto the server now?')
        ->call('checkForUpdates')
        ->assertDispatched('success', ['message' => '2 commit(s) to pull']);

    Process::assertRan(fn ($process) => $process->command === ['git', 'fetch', '--quiet'] && $process->path === '/var/www/qloud');
});

it('says when the server is up to date or git cannot be read', function (): void {
    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('selectTab', 'server')
        ->assertSee('Up to date')
        ->call('checkForUpdates')
        ->assertDispatched('success', ['message' => 'Already up to date']);

    Process::fake(fn ($process) => Process::result(errorOutput: 'fatal: detected dubious ownership in repository', exitCode: 128));

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('selectTab', 'server')
        ->assertSee('detected dubious ownership')
        ->call('checkForUpdates')
        ->assertDispatched('error', ['message' => 'git fetch failed: fatal: detected dubious ownership in repository']);
});

it('rejects unknown actions and non super admins', function (): void {
    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id])
        ->call('runServerAction', 'rm')
        ->assertDispatched('error', ['message' => 'Unknown server action']);

    Process::assertNothingRan();

    $component = Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->tenant->id]);
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $component->call('runServerAction', 'pull')->assertForbidden();

    Process::assertNothingRan();
});
