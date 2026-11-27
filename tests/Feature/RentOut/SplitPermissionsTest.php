<?php

use App\Actions\RentOut\Security\CreateAction as CreateSecurityAction;
use App\Enums\RentOut\SecurityStatus;
use App\Livewire\RentOut\Tabs\DocumentsTab;
use App\Livewire\RentOut\Tabs\ExtendModal;
use App\Livewire\RentOut\Tabs\ExtendTab;
use App\Livewire\RentOut\Tabs\PaymentTab;
use App\Livewire\RentOut\Tabs\PayoutModal;
use App\Livewire\RentOut\Tabs\SecurityModal;
use App\Livewire\RentOut\Tabs\ServicesTab;
use App\Livewire\SupplyRequest\Create as SupplyRequestCreate;
use App\Models\Product;
use App\Models\RentOut;
use App\Models\RentOutExtend;
use App\Models\RentOutSecurity;
use App\Models\Role;
use App\Models\SupplyRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Permissions carried over from the old system that split an action off a
 * broader permission: extension edit/delete, payment and service edit, the
 * security status and payment, document download, supply unit price and
 * the per-type rent-out reports.
 */
beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->tenantId = $this->user->tenant_id;
    session(['branch_id' => 1]);

    $this->grant = function (string ...$names): void {
        foreach ($names as $name) {
            $this->user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
    };

    $this->accountId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Tenant '.Str::random(6), 'account_type' => 'asset',
    ]);
    $groupId = DB::table('property_groups')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => 1, 'name' => 'Group '.Str::random(6)]);
    $buildingId = DB::table('property_buildings')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6)]);
    $typeId = DB::table('property_types')->insertGetId(['tenant_id' => $this->tenantId, 'name' => 'Studio '.Str::random(6)]);
    $this->propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId,
        'property_building_id' => $buildingId, 'property_type_id' => $typeId, 'number' => '7701', 'ownership' => 'Owner',
    ]);

    $this->rentOut = function (string $type = 'rental') use ($groupId, $buildingId, $typeId): RentOut {
        return RentOut::find(DB::table('rent_outs')->insertGetId([
            'tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_id' => $this->propertyId,
            'property_group_id' => $groupId, 'property_building_id' => $buildingId, 'property_type_id' => $typeId,
            'account_id' => $this->accountId, 'agreement_type' => $type,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]));
    };

    $this->extension = fn (RentOut $rentOut, string $endDate = '2027-12-31'): RentOutExtend => RentOutExtend::create([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'rent_out_id' => $rentOut->id,
        'start_date' => '2026-12-31', 'end_date' => $endDate, 'rent_amount' => 5000, 'payment_mode' => 'cash',
    ]);
});

it('needs extend delete, not just extend, to delete an extension', function (): void {
    $rentOut = ($this->rentOut)();
    $extend = ($this->extension)($rentOut);
    ($this->grant)('rent out.extend');

    Livewire::test(ExtendTab::class, ['rentOutId' => $rentOut->id])
        ->assertDontSeeHtml("deleteExtend({$extend->id})")
        ->call('deleteExtend', $extend->id)
        ->assertForbidden();

    ($this->grant)('rent out.extend delete');
    Livewire::test(ExtendTab::class, ['rentOutId' => $rentOut->id])->call('deleteExtend', $extend->id);

    expect(RentOutExtend::find($extend->id))->toBeNull();
});

it('edits an extension with extend edit and moves the agreement end date along', function (): void {
    $rentOut = ($this->rentOut)();
    $extend = ($this->extension)($rentOut);

    Livewire::test(ExtendTab::class, ['rentOutId' => $rentOut->id])->call('editExtend', $extend->id)->assertForbidden();

    ($this->grant)('rent out.extend edit');
    Livewire::test(ExtendTab::class, ['rentOutId' => $rentOut->id])
        ->assertSeeHtml("editExtend({$extend->id})")
        ->call('editExtend', $extend->id)
        ->assertDispatched('open-extend-modal');

    Livewire::test(ExtendModal::class)
        ->call('openModal', ['rent_out_id' => $rentOut->id, 'start_date' => '2026-12-31', 'end_date' => '2028-06-30', 'rent_amount' => 6000, 'payment_mode' => 'cash'], $extend->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(RentOutExtend::count())->toBe(1)
        ->and((float) $extend->fresh()->rent_amount)->toBe(6000.0)
        ->and($rentOut->fresh()->end_date->format('Y-m-d'))->toBe('2028-06-30');
});

it('keeps the lease extension permissions apart from the rental ones', function (): void {
    $lease = ($this->rentOut)('lease');
    $extend = ($this->extension)($lease);
    ($this->grant)('rent out.extend delete');

    Livewire::test(ExtendTab::class, ['rentOutId' => $lease->id])->call('deleteExtend', $extend->id)->assertForbidden();
});

it('needs payment edit to edit or delete a recorded payment', function (): void {
    $rentOut = ($this->rentOut)();
    $paymentId = DB::table('rent_out_transactions')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'rent_out_id' => $rentOut->id,
        'date' => '2026-02-01', 'credit' => 100, 'debit' => 0, 'source' => 'PaymentTerm',
    ]);
    ($this->grant)('rent out.view', 'rent out.payment');

    Livewire::test(PaymentTab::class, ['rentOutId' => $rentOut->id])
        ->assertDontSeeHtml("deletePayment({$paymentId})")
        ->call('editPayment', $paymentId)->assertForbidden();
    Livewire::test(PaymentTab::class, ['rentOutId' => $rentOut->id])->call('deletePayment', $paymentId)->assertForbidden();
    Livewire::test(PayoutModal::class)->call('editPayment', $paymentId)->assertForbidden();

    ($this->grant)('rent out.payment edit');
    Livewire::test(PayoutModal::class)->call('editPayment', $paymentId)->assertSet('editingId', $paymentId);
});

it('needs service edit to edit a service payment', function (): void {
    $lease = ($this->rentOut)('lease');
    $paymentId = DB::table('rent_out_transactions')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'rent_out_id' => $lease->id,
        'date' => '2026-02-01', 'credit' => 50, 'debit' => 0, 'source' => 'Service',
    ]);
    ($this->grant)('rent out lease.payment', 'rent out.service edit');

    Livewire::test(ServicesTab::class, ['rentOutId' => $lease->id])->call('editPayment', $paymentId)->assertForbidden();

    ($this->grant)('rent out lease.service edit');
    Livewire::test(ServicesTab::class, ['rentOutId' => $lease->id])->call('editPayment', $paymentId)->assertDispatched('edit-service-payment');
});

it('keeps a deposit status unless the user may change it', function (bool $canChange, SecurityStatus $expected): void {
    $rentOut = ($this->rentOut)();
    $security = (new CreateSecurityAction())->execute([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'rent_out_id' => $rentOut->id,
        'amount' => 1000, 'type' => 'deposit', 'status' => SecurityStatus::Submitted->value, 'due_date' => '2026-01-10',
    ])['data'];
    ($this->grant)('rent out security.edit');
    if ($canChange) {
        ($this->grant)('rent out security.status change');
    }

    Livewire::test(SecurityModal::class)
        ->call('openModal', ['rent_out_id' => $rentOut->id, 'amount' => 1000, 'type' => 'deposit', 'status' => SecurityStatus::Overdue->value, 'due_date' => '2026-01-10'], $security->id)
        ->assertSet('form.status', SecurityStatus::Overdue->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(RentOutSecurity::find($security->id)->status)->toBe($expected);
})->with([
    'without status change' => [false, SecurityStatus::Submitted],
    'with status change' => [true, SecurityStatus::Overdue],
]);

it('needs document download to download an agreement document', function (): void {
    $rentOut = ($this->rentOut)();
    $documentId = DB::table('rent_out_documents')->insertGetId([
        'tenant_id' => $this->tenantId, 'rent_out_id' => $rentOut->id, 'name' => 'lease.pdf', 'path' => 'public/missing/lease.pdf',
    ]);

    Livewire::test(DocumentsTab::class, ['rentOutId' => $rentOut->id])->call('downloadDocument', $documentId)->assertForbidden();

    ($this->grant)('rent out document.download');
    Livewire::test(DocumentsTab::class, ['rentOutId' => $rentOut->id])->call('downloadDocument', $documentId)->assertDispatched('error');
});

it('opens each rent-out report only with that agreement type report permission', function (string $route, string $report): void {
    ($this->grant)('rent out.view', 'rent out lease.view', 'rent out security.view');
    $this->get(route($route, 'rental'))->assertForbidden();

    ($this->grant)("rent out.{$report}");
    $this->get(route($route, 'rental'))->assertSuccessful();
    $this->get(route($route, 'lease'))->assertForbidden();

    ($this->grant)("rent out lease.{$report}");
    $this->get(route($route, 'lease'))->assertSuccessful();
})->with([
    'daybook' => ['property::report::daybook', 'daybook'],
    'security report' => ['property::report::security', 'security report'],
    'customer property' => ['property::report::customer-property', 'customer property report'],
]);

it('gates payment history and the service charge report on their own permissions', function (): void {
    ($this->grant)('rent out.payment', 'rent out lease.view');
    $this->get(route('property::rent::payment-history'))->assertForbidden();
    $this->get(route('property::report::service-charge'))->assertForbidden();

    ($this->grant)('rent out.payment history', 'rent out lease.service charge report');
    $this->get(route('property::rent::payment-history'))->assertSuccessful();
    $this->get(route('property::report::service-charge'))->assertSuccessful();
});

it('prices supply items at product cost without unit price edit', function (bool $canEditPrice, float $expected): void {
    $product = Product::factory()->create(['tenant_id' => $this->tenantId, 'cost' => 10]);
    ($this->grant)('supply request.create');
    if ($canEditPrice) {
        ($this->grant)('supply request.unit price edit');
    }

    Livewire::test(SupplyRequestCreate::class)
        ->call('getPropertyDetails', $this->propertyId)
        ->set('item.product_id', $product->id)
        ->set('item.unit_price', 99)
        ->call('addCart')
        ->set('items.0.unit_price', 77)
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotDispatched('error');

    expect((float) SupplyRequestItem::where('product_id', $product->id)->value('unit_price'))->toBe($expected);
})->with([
    'without unit price edit' => [false, 10.0],
    'with unit price edit' => [true, 77.0],
]);

it('needs upload attachment after completed to attach files to a completed request', function (): void {
    Livewire::test(SupplyRequestCreate::class)->call('uploadCompletedAttachments')->assertForbidden();
});

it('grants the split permissions to roles holding the permission that used to cover them', function (): void {
    $leasing = Role::create(['name' => 'Leasing '.Str::random(4), 'guard_name' => 'web', 'tenant_id' => $this->tenantId]);
    $leasing->givePermissionTo(Permission::findOrCreate('rent out.extend', 'web'), Permission::findOrCreate('rent out.payment', 'web'));
    $viewer = Role::create(['name' => 'Viewer '.Str::random(4), 'guard_name' => 'web', 'tenant_id' => $this->tenantId]);
    $viewer->givePermissionTo(Permission::findOrCreate('rent out.view', 'web'));

    (require database_path('migrations/2026_11_27_204544_grant_split_property_permissions.php'))->up();

    expect($leasing->fresh()->hasPermissionTo('rent out.extend delete'))->toBeTrue()
        ->and($leasing->fresh()->hasPermissionTo('rent out.payment edit'))->toBeTrue()
        ->and($leasing->fresh()->hasPermissionTo('rent out.daybook'))->toBeFalse()
        ->and($viewer->fresh()->hasPermissionTo('rent out.daybook'))->toBeTrue()
        ->and($viewer->fresh()->hasPermissionTo('rent out.extend delete'))->toBeFalse()
        ->and(Permission::where('name', 'supply request.upload attachment after completed')->first()->roles()->count())->toBe(0);
});
