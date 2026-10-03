<?php

use App\Livewire\RentOut\Tabs\ServicesTab;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Models\RentOutTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Services tab prints a receipt for a credit (payment) row and a payment
 * voucher for a debit (charge) row.
 */
it('prints a receipt for credit rows and a payment voucher for debit rows', function (): void {
    $tenant = Tenant::create(['name' => 'ST Tenant', 'subdomain' => 'st'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'S']);

    $user = User::create([
        'tenant_id' => $tenant->id, 'name' => 'ST User', 'type' => 'employee', 'is_admin' => 1,
        'email' => 'st'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'ST Customer']);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'ST Branch', 'code' => 'ST']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'ST Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'ST Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'ST Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'ST-101',
    ]);
    $rentOut = RentOut::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $user->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(), 'created_by' => $user->id,
    ]);

    $row = fn (float $credit, float $debit): RentOutTransaction => RentOutTransaction::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'rent_out_id' => $rentOut->id,
        'date' => now()->toDateString(), 'credit' => $credit, 'debit' => $debit,
        'source' => 'Service', 'created_by' => $user->id,
    ]);
    $payment = $row(1000, 0);
    $charge = $row(0, 1000);

    $this->actingAs($user);
    $user->givePermissionTo(Permission::findOrCreate('rent out service.delete', 'web'));

    Livewire::test(ServicesTab::class, ['rentOutId' => $rentOut->id])
        ->call('printReceipt', $payment->id)
        ->assertDispatched('open-receipt-tab', url: route('print::rentout::payment-receipt', $payment->id))
        ->call('printReceipt', $charge->id)
        ->assertDispatched('open-receipt-tab', url: route('print::rentout::payment-voucher', $charge->id))
        ->assertSee('Print Receipt')
        ->assertSee('Print Voucher')
        ->call('deletePayment', $payment->id)
        ->assertSeeHtml('<td class="text-end fw-bold">1,000.00</td>')
        ->call('deletePayment', $charge->id)
        ->assertDispatched('success', message: 'Service payment deleted.')
        ->assertDispatched('rent-out-updated');

    expect(RentOutTransaction::find($charge->id))->toBeNull()
        ->and(RentOutTransaction::find($payment->id))->toBeNull();
});
