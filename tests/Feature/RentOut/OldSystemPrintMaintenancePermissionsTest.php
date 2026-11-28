<?php

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Livewire\Maintenance\Complaint;
use App\Livewire\Maintenance\Page as MaintenancePage;
use App\Livewire\RentOut\Tabs\ExtendTab;
use App\Livewire\RentOut\Tabs\PaymentTab;
use App\Livewire\RentOut\Tabs\ServicesTab;
use App\Models\Maintenance;
use App\Models\MaintenanceComplaint;
use App\Models\RentOut;
use App\Models\RentOutExtend;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The last old-system property permissions that had no equivalent: per-type
 * SOA statement / SOA utilities / receipt print / extended residential lease,
 * and maintenance cancel, delete item and technician edit.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->user = $this->world->user;
    $this->actingAs($this->user);
    $this->tenantId = $this->world->tenant->id;
    $branchId = $this->world->branch->id;
    session(['branch_id' => $branchId]);

    // Print and page routes resolve the tenant from the host.
    $this->url = fn (string $name, mixed $parameters = []): string => $this->world->url(route($name, $parameters, false));

    $this->grant = function (string ...$names): void {
        foreach ($names as $name) {
            $this->user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
    };

    $this->accountId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Tenant '.Str::random(6), 'account_type' => 'asset',
    ]);
    $groupId = DB::table('property_groups')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'name' => 'Group '.Str::random(6)]);
    $buildingId = DB::table('property_buildings')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6)]);
    $typeId = DB::table('property_types')->insertGetId(['tenant_id' => $this->tenantId, 'name' => 'Studio '.Str::random(6)]);
    $this->propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'property_group_id' => $groupId,
        'property_building_id' => $buildingId, 'property_type_id' => $typeId, 'number' => '8801', 'ownership' => 'Owner',
    ]);

    $this->rentOut = function (string $type = 'rental') use ($groupId, $buildingId, $typeId, $branchId): RentOut {
        return RentOut::find(DB::table('rent_outs')->insertGetId([
            'tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'property_id' => $this->propertyId,
            'property_group_id' => $groupId, 'property_building_id' => $buildingId, 'property_type_id' => $typeId,
            'account_id' => $this->accountId, 'agreement_type' => $type,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]));
    };

    $this->payment = fn (RentOut $rentOut, string $source = 'PaymentTerm'): int => DB::table('rent_out_transactions')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'rent_out_id' => $rentOut->id,
        'date' => '2026-02-01', 'credit' => 100, 'debit' => 0, 'source' => $source,
    ]);

    $this->maintenance = function () use ($branchId): Maintenance {
        $categoryId = DB::table('complaint_categories')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'name' => 'Plumbing '.Str::random(4)]);
        $complaintId = DB::table('complaints')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'complaint_category_id' => $categoryId, 'name' => 'Leak']);
        $maintenance = Maintenance::create([
            'tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'property_id' => $this->propertyId,
            'date' => '2026-11-28', 'priority' => 'medium', 'status' => MaintenanceStatus::Pending->value,
        ]);
        MaintenanceComplaint::create([
            'tenant_id' => $this->tenantId, 'branch_id' => $branchId, 'maintenance_id' => $maintenance->id,
            'complaint_id' => $complaintId, 'status' => MaintenanceComplaintStatus::Pending->value,
        ]);

        return $maintenance;
    };
});

it('opens each SOA print only with that agreement type SOA permission', function (string $route, string $permission): void {
    $rental = ($this->rentOut)();
    $lease = ($this->rentOut)('lease');
    ($this->grant)('rent out.print', 'rent out lease.print');

    $this->get(($this->url)($route, $rental->id))->assertForbidden();
    $this->get(($this->url)($route, $lease->id))->assertForbidden();

    ($this->grant)("rent out lease.{$permission}");
    $this->get(($this->url)($route, $rental->id))->assertForbidden();

    $this->user->syncPermissions([]);
    ($this->grant)("rent out.{$permission}");
    $this->get(($this->url)($route, $lease->id))->assertForbidden();
})->with([
    'statement' => ['print::rentout::statement', 'soa statement'],
    'utilities' => ['print::rentout::utilities-statement', 'soa utilities'],
]);

it('shows the SOA buttons only with the SOA permissions', function (): void {
    $rental = ($this->rentOut)();
    ($this->grant)('rent out.view');

    $this->get(($this->url)('property::rent::view', $rental->id))
        ->assertSuccessful()
        ->assertDontSeeHtml('data-bs-target="#SOAStatementModal"')
        ->assertDontSeeHtml('data-bs-target="#SOAUtilitiesModal"');

    ($this->grant)('rent out.soa statement', 'rent out.soa utilities');
    $this->get(($this->url)('property::rent::view', $rental->id))
        ->assertSeeHtml('data-bs-target="#SOAStatementModal"')
        ->assertSeeHtml('data-bs-target="#SOAUtilitiesModal"');
});

it('prints a payment receipt only with that agreement type print permission', function (): void {
    $lease = ($this->rentOut)('lease');
    $paymentId = ($this->payment)($lease);
    ($this->grant)('rent out lease.payment', 'rent out.print');

    $this->get(($this->url)('print::rentout::payment-receipt', $paymentId))->assertForbidden();
    $this->get(($this->url)('print::rentout::payment-voucher', $paymentId))->assertForbidden();
    Livewire::test(PaymentTab::class, ['rentOutId' => $lease->id])->assertDontSee('Print Receipt');

    ($this->grant)('rent out lease.print');
    Livewire::test(PaymentTab::class, ['rentOutId' => $lease->id])->assertSee('Print Receipt');
});

it('needs the print permission to print a service receipt', function (): void {
    $lease = ($this->rentOut)('lease');
    $paymentId = ($this->payment)($lease, 'Service');

    Livewire::test(ServicesTab::class, ['rentOutId' => $lease->id])
        ->assertDontSee('Print Receipt')
        ->call('printReceipt', $paymentId)
        ->assertForbidden();

    ($this->grant)('rent out lease.print');
    Livewire::test(ServicesTab::class, ['rentOutId' => $lease->id])
        ->assertSee('Print Receipt')
        ->call('printReceipt', $paymentId)
        ->assertDispatched('open-receipt-tab');
});

it('prints the extended residential lease from the latest extension with the agreement permission', function (): void {
    $rental = ($this->rentOut)();
    $extend = fn (string $start, string $end): RentOutExtend => RentOutExtend::create([
        'tenant_id' => $this->tenantId, 'branch_id' => $this->world->branch->id, 'rent_out_id' => $rental->id,
        'start_date' => $start, 'end_date' => $end, 'rent_amount' => 5000, 'payment_mode' => 'cash',
    ]);
    $extend('2026-12-31', '2027-12-31');
    $extend('2027-12-31', '2028-12-31');
    $printUrl = route('print::rentout::residential-lease', [$rental->id, 'extended'], false);
    $printPath = ($this->url)('print::rentout::residential-lease', [$rental->id, 'extended']);
    ($this->grant)('rent out booking.residential lease');

    Livewire::test(ExtendTab::class, ['rentOutId' => $rental->id])->assertDontSeeHtml($printUrl);
    $this->get($printPath)->assertForbidden();

    ($this->grant)('rent out.residential lease');
    $html = Livewire::test(ExtendTab::class, ['rentOutId' => $rental->id])->html();
    expect(substr_count($html, $printUrl))->toBe(1);
});

it('cancels a pending maintenance request and its open complaints only with maintenance cancel', function (): void {
    $maintenance = ($this->maintenance)();
    ($this->grant)('maintenance.edit');

    Livewire::test(MaintenancePage::class, ['id' => $maintenance->id])
        ->assertDontSee('Cancel Request')
        ->call('cancel')
        ->assertForbidden();

    ($this->grant)('maintenance.cancel');
    Livewire::test(MaintenancePage::class, ['id' => $maintenance->id])
        ->assertSee('Cancel Request')
        ->call('cancel')
        ->assertRedirect(route('property::maintenance::index'));

    expect($maintenance->fresh()->status)->toBe(MaintenanceStatus::Cancelled)
        ->and($maintenance->maintenanceComplaints()->first()->status)->toBe(MaintenanceComplaintStatus::Cancelled);
});

it('needs delete item to remove a saved complaint from a maintenance request', function (): void {
    $maintenance = ($this->maintenance)();
    ($this->grant)('maintenance.edit');

    Livewire::test(MaintenancePage::class, ['id' => $maintenance->id])
        ->assertDontSeeHtml('removeComplaint(0)')
        ->call('removeComplaint', 0)
        ->assertForbidden();

    ($this->grant)('maintenance.delete item');
    Livewire::test(MaintenancePage::class, ['id' => $maintenance->id])
        ->assertSeeHtml('removeComplaint(0)')
        ->call('removeComplaint', 0)
        ->assertSet('complaints', []);
});

it('needs technician edit to save a technician remark', function (): void {
    $complaint = ($this->maintenance)()->maintenanceComplaints()->first();
    ($this->grant)('maintenance.view');

    Livewire::test(Complaint::class, ['id' => $complaint->id])
        ->assertDontSeeHtml("wire:click=\"save('pending')\"")
        ->set('technician_remark', 'Fixed the tap')
        ->call('save', 'pending')
        ->assertForbidden();

    ($this->grant)('maintenance.technician edit');
    Livewire::test(Complaint::class, ['id' => $complaint->id])
        ->assertSeeHtml("wire:click=\"save('pending')\"")
        ->set('technician_remark', 'Fixed the tap')
        ->call('save', 'pending')
        ->assertDispatched('success');

    expect($complaint->fresh()->technician_remark)->toBe('Fixed the tap');
});

it('grants the new permissions to roles that could do the action until now', function (): void {
    $role = fn (string ...$permissions): Role => tap(
        Role::create(['name' => 'Role '.Str::random(6), 'guard_name' => 'web', 'tenant_id' => $this->tenantId]),
        fn (Role $role) => $role->givePermissionTo(array_map(fn ($name) => Permission::findOrCreate($name, 'web'), $permissions)),
    );
    $printer = $role('rent out.print');
    $collector = $role('rent out lease.payment');
    $booker = $role('rent out booking.residential lease');
    $maintainer = $role('maintenance.view', 'maintenance.edit');

    (require database_path('migrations/2026_11_28_095525_grant_print_soa_maintenance_permissions.php'))->up();

    expect($printer->fresh()->hasPermissionTo('rent out lease.soa statement'))->toBeTrue()
        ->and($printer->fresh()->hasPermissionTo('rent out.soa utilities'))->toBeTrue()
        ->and($printer->fresh()->hasPermissionTo('rent out lease.print'))->toBeTrue()
        ->and($collector->fresh()->hasPermissionTo('rent out lease.print'))->toBeTrue()
        ->and($collector->fresh()->hasPermissionTo('rent out lease.soa statement'))->toBeFalse()
        ->and($booker->fresh()->hasPermissionTo('rent out.residential lease'))->toBeTrue()
        ->and($maintainer->fresh()->hasPermissionTo('maintenance.delete item'))->toBeTrue()
        ->and($maintainer->fresh()->hasPermissionTo('maintenance.technician edit'))->toBeTrue()
        ->and($maintainer->fresh()->hasPermissionTo('maintenance.cancel'))->toBeFalse();
});
