<?php

use App\Livewire\Log\FailedJobs;
use App\Livewire\Log\Jobs;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

test('jobs log page can be rendered for authorized users', function (): void {
    $user = createLogUser();

    $this->actingAs($user)
        ->get(route('log::jobs'))
        ->assertSuccessful()
        ->assertSeeLivewire(Jobs::class);
});

test('failed jobs log page can be rendered for authorized users', function (): void {
    $user = createLogUser();

    $this->actingAs($user)
        ->get(route('log::failed_jobs'))
        ->assertSuccessful()
        ->assertSeeLivewire(FailedJobs::class);
});

test('jobs livewire component shows readable statuses', function (): void {
    DB::table('jobs')->insert([
        [
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SyncReportsJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            ]),
            'attempts' => 1,
            'reserved_at' => null,
            'available_at' => now()->subMinute()->timestamp,
            'created_at' => now()->subMinutes(2)->timestamp,
        ],
        [
            'queue' => 'imports',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\ImportVendorsJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            ]),
            'attempts' => 2,
            'reserved_at' => null,
            'available_at' => now()->subMinute()->timestamp,
            'created_at' => now()->subMinutes(3)->timestamp,
        ],
        [
            'queue' => 'emails',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendDigestJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            ]),
            'attempts' => 1,
            'reserved_at' => now()->subSeconds(30)->timestamp,
            'available_at' => now()->subMinute()->timestamp,
            'created_at' => now()->subMinutes(4)->timestamp,
        ],
    ]);

    Livewire::test(Jobs::class)
        ->assertSee('SyncReportsJob')
        ->assertSee('Queued')
        ->assertSee('Retrying')
        ->assertSee('Processing');
});

test('failed jobs livewire component shows readable job names and error summaries', function (): void {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'exports',
        'payload' => json_encode([
            'displayName' => 'App\\Jobs\\ExportInventoryLogJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        ]),
        'exception' => "RuntimeException: Export pipeline crashed\n#0 /var/www/html/app/Jobs/ExportInventoryLogJob.php(10): throw new RuntimeException()",
        'failed_at' => now(),
    ]);

    Livewire::test(FailedJobs::class)
        ->assertSee('ExportInventoryLogJob')
        ->assertSee('Failed')
        ->assertSee('RuntimeException: Export pipeline crashed');
});

function createLogUser(): User
{
    $tenant = Tenant::query()->create([
        'name' => 'Test Tenant',
        'code' => 'TEST',
        'subdomain' => 'test-tenant',
        'domain' => 'test.local',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $permission = Permission::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'log.inventory',
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($permission);

    return $user;
}
