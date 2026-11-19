<?php

use App\Livewire\Audit\Table as AuditTable;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Pages that read shared tables (audits, the public export folder, the queue
 * tables, database backups) must only ever show the current tenant's data.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->other = PosWorld::create();
    $this->world = PosWorld::create();
    foreach (['log.inventory', 'backup.view'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

function auditFor(Product $product): Audit
{
    return Audit::query()->create([
        'event' => 'updated',
        'auditable_type' => Product::class,
        'auditable_id' => $product->id,
        'old_values' => ['name' => 'secret-old'],
        'new_values' => ['name' => 'secret-new'],
    ]);
}

it('shows the audit trail of own records only', function (): void {
    auditFor($this->world->product);
    auditFor($this->other->product);

    Livewire::test(AuditTable::class, ['model' => 'Product', 'table_id' => $this->world->product->id])
        ->assertSee('secret-new');

    Livewire::test(AuditTable::class, ['model' => 'Product', 'table_id' => $this->other->product->id])
        ->assertDontSee('secret-new');
});

it('builds export paths inside the tenant folder with an unguessable name', function (): void {
    $path = tenantExportPath('Sale');

    expect($path)->toStartWith('exports/'.$this->world->tenant->id.'/Sale_')
        ->and($path)->not->toBe(tenantExportPath('Sale'));
});

it('refuses to open another tenant export in the excel viewer', function (): void {
    $this->get($this->world->url(route('notification::excel-view', ['path' => tenantExportPath('Sale', $this->other->tenant->id)], absolute: false)))
        ->assertRedirect()
        ->assertSessionHas('error', 'Invalid file path.');
});

it('limits the shared queue logs and database backups to platform admins', function (string $routeName): void {
    $this->get($this->world->url(route($routeName, absolute: false)))->assertForbidden();
})->with(['log::jobs', 'log::failed_jobs', 'backup::index']);
