<?php

use App\Helpers\RentOutTransactionHelper;
use App\Livewire\RentOut\ServicePaymentTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * The services list is shared by Rent and Sale; each row's customer link must
 * open the agreement under the module its agreement_type belongs to.
 */
function rstlRentOut(int $tenantId, string $agreementType): int
{
    $account = fn (string $name, string $type) => DB::table('accounts')->insertGetId([
        'tenant_id' => $tenantId, 'name' => $name.' '.Str::random(6), 'account_type' => $type,
    ]);

    $groupId = DB::table('property_groups')->insertGetId([
        'tenant_id' => $tenantId, 'branch_id' => 1, 'name' => 'Group '.Str::random(6),
    ]);
    $buildingId = DB::table('property_buildings')->insertGetId([
        'tenant_id' => $tenantId, 'branch_id' => 1, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6),
    ]);
    $typeId = DB::table('property_types')->insertGetId([
        'tenant_id' => $tenantId, 'name' => 'Studio '.Str::random(6),
    ]);
    $propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $tenantId,
        'branch_id' => 1,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'number' => '9301',
        'ownership' => 'Owner',
    ]);

    $rentOutId = DB::table('rent_outs')->insertGetId([
        'tenant_id' => $tenantId,
        'branch_id' => 1,
        'property_id' => $propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'account_id' => $account('Customer', 'asset'),
        'agreement_type' => $agreementType,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
    ]);

    $response = (new RentOutTransactionHelper())->storeServicePayLater($rentOutId, [
        'date' => now()->toDateString(),
        'amount' => 100,
        'category' => (string) $account('Service Income', 'income'),
        'account_id' => (string) $account('Card', 'asset'),
        'remark' => 'Service',
    ]);
    expect($response['success'])->toBeTrue();

    return $rentOutId;
}

beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    session(['branch_id' => 1]);
});

it('links a lease service row to the sale view', function () {
    $rentOutId = rstlRentOut($this->tenantId, 'lease');

    Livewire::test(ServicePaymentTable::class, ['agreementType' => 'lease'])
        ->assertSee(route('property::sale::view', $rentOutId), false)
        ->assertDontSee(route('property::rent::view', $rentOutId), false);
});

it('links a rental service row to the rent view', function () {
    $rentOutId = rstlRentOut($this->tenantId, 'rental');

    Livewire::test(ServicePaymentTable::class, ['agreementType' => 'rental'])
        ->assertSee(route('property::rent::view', $rentOutId), false)
        ->assertDontSee(route('property::sale::view', $rentOutId), false);
});

it('hides the per-row balance column on the sale services list', function () {
    rstlRentOut($this->tenantId, 'lease');

    Livewire::test(ServicePaymentTable::class, ['agreementType' => 'lease'])
        ->assertSet('visibleColumns', fn (array $columns) => ! in_array('balance', $columns))
        ->assertDontSeeHtml("toggleColumn('balance')")
        ->assertDontSeeHtml('<th class="fw-semibold text-end pe-3">Balance</th>');
});

it('keeps the per-row balance column on the rent services list', function () {
    rstlRentOut($this->tenantId, 'rental');

    Livewire::test(ServicePaymentTable::class, ['agreementType' => 'rental'])
        ->assertSeeHtml("toggleColumn('balance')")
        ->assertSeeHtml('<th class="fw-semibold text-end pe-3">Balance</th>');
});
