<?php

use App\Actions\RentOut\Security\CreateAction;
use App\Enums\RentOut\SecurityStatus;
use App\Livewire\RentOut\Tabs\SecurityModal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Security deposits use the old system's six statuses. Each status decides
 * what lands in the ledger: held/awaited deposits post nothing, received ones
 * post the collection receipt, and released ones post the receipt + refund.
 */
beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    session(['branch_id' => 1]);

    $account = fn (string $name, string $type) => DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => $name.' '.Str::random(6), 'account_type' => $type,
    ]);

    $this->customerId = $account('Tenant Customer', 'asset');
    $this->cashId = $account('Cash', 'asset');

    $groupId = DB::table('property_groups')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'name' => 'Group '.Str::random(6),
    ]);
    $buildingId = DB::table('property_buildings')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6),
    ]);
    $typeId = DB::table('property_types')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Studio '.Str::random(6),
    ]);
    $propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'number' => '9301',
        'ownership' => 'Owner',
    ]);

    $this->rentOutId = DB::table('rent_outs')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_id' => $propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'account_id' => $this->customerId,
        'agreement_type' => 'lease',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
});

it('offers the six old-system statuses in the old order', function (): void {
    expect(array_map(fn (SecurityStatus $status) => $status->label(), SecurityStatus::cases()))
        ->toBe(['Deposited', 'Submitted', 'Returned', 'Paid', 'Overdue', 'Paid & Released']);
});

it('posts the ledger entries each status calls for', function (SecurityStatus $status, array $expectedCategories): void {
    $response = (new CreateAction())->execute([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'rent_out_id' => $this->rentOutId,
        'amount' => 30000,
        'account_id' => $this->cashId,
        'type' => 'deposit',
        'status' => $status->value,
        'due_date' => '2026-10-01',
        'collected_date' => '2026-10-01',
        'returned_date' => '2026-12-31',
    ]);

    expect($response['success'])->toBeTrue($response['message'] ?? '');

    $categories = DB::table('rent_out_transactions')
        ->where('model', 'RentOutSecurity')
        ->where('model_id', $response['data']->id)
        ->whereNull('deleted_at')
        ->where('account_id', $this->cashId)
        ->orderBy('id')
        ->pluck('category')
        ->all();

    expect($categories)->toBe($expectedCategories);
})->with([
    'submitted: cheque only held' => [SecurityStatus::Submitted, []],
    'overdue: nothing received' => [SecurityStatus::Overdue, []],
    'deposited: banked' => [SecurityStatus::Deposited, ['security_collection']],
    'paid: cash received' => [SecurityStatus::Paid, ['security_collection']],
    'returned: refunded' => [SecurityStatus::Returned, ['security_collection', 'security_refund']],
    'paid & released: refunded at end of contract' => [SecurityStatus::PaidReleased, ['security_collection', 'security_refund']],
]);

it('requires the returned date once a deposit is paid & released', function (): void {
    auth()->user()->givePermissionTo(Permission::findOrCreate('rent out security.create', 'web'));

    Livewire::test(SecurityModal::class)
        ->call('openModal', [
            'rent_out_id' => $this->rentOutId,
            'amount' => 30000,
            'account_id' => $this->cashId,
            'type' => 'deposit',
            'status' => SecurityStatus::PaidReleased->value,
            'due_date' => '2026-10-01',
            'collected_date' => '2026-10-01',
        ])
        ->call('save')
        ->assertHasErrors(['form.returned_date' => 'required'])
        ->assertHasNoErrors(['form.collected_date']);
});
