<?php

use App\Actions\RentOut\Comparison\CompareRentOutPopulationAction;
use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Livewire\RentOut\PaymentTable;
use App\Models\RentOutPaymentTerm;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * The payment-mode cards on the rent-out Payments page total payment terms by
 * their payment_mode, so every term must carry one: new terms take the
 * agreement's collection mode, and migrated terms keep the mode accounts held.
 */
beforeEach(function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    app(TenantService::class)->setCurrentTenant(Tenant::find($this->tenantId));
    session(['branch_id' => 1]);

    $customerId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Mode Customer '.Str::random(6), 'account_type' => 'asset',
    ]);
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
        'number' => 'PM-101',
        'ownership' => 'Owner',
    ]);

    $this->makeRentOut = fn (string $collectionMode) => DB::table('rent_outs')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_id' => $propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'account_id' => $customerId,
        'agreement_type' => 'rental',
        'status' => 'occupied',
        'collection_payment_mode' => $collectionMode,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $this->makeTerm = fn (int $rentOutId, float $amount, array $extra = []) => RentOutPaymentTerm::create([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'rent_out_id' => $rentOutId,
        'amount' => $amount,
        'discount' => 0,
        'due_date' => '2026-02-01',
        ...$extra,
    ]);
});

it('gives a new term the agreement collection payment mode', function (): void {
    $term = ($this->makeTerm)(($this->makeRentOut)('cheque'), 1000);

    expect($term->fresh()->payment_mode)->toBe('cheque');
});

it('keeps a payment mode set explicitly on the term', function (): void {
    $term = ($this->makeTerm)(($this->makeRentOut)('cheque'), 1000, ['payment_mode' => 'pos']);

    expect($term->fresh()->payment_mode)->toBe('pos');
});

it('totals each payment mode card from its terms', function (): void {
    ($this->makeTerm)(($this->makeRentOut)('cheque'), 4000);
    ($this->makeTerm)(($this->makeRentOut)('bank_transfer'), 2500);

    $statistics = collect(Livewire::test(PaymentTable::class, ['agreementType' => 'rental'])
        ->set('dateFrom', '')
        ->set('dateTo', '')
        ->instance()->statistics)->keyBy('mode');

    expect((float) $statistics['Cheque']['total_amount'])->toBe(4000.0)
        ->and((float) $statistics['Bank Transfer']['total_amount'])->toBe(2500.0)
        ->and((float) $statistics['Cash']['total_amount'])->toBe(0.0)
        ->and((float) $statistics['GRAND TOTAL']['total_amount'])->toBe(6500.0);
});

it('migrates accounts payment mode constants rather than account heads', function (?int $oldModeId, string $expected): void {
    $command = new MigratePropertyDataCommand();

    expect((fn () => $this->resolvePaymentMode($oldModeId))->call($command))->toBe($expected);
})->with([
    'cash' => [1, 'cash'],
    'cheque' => [2, 'cheque'],
    'pos' => [3, 'pos'],
    'bank transfer' => [4, 'bank_transfer'],
    'unset' => [null, 'cash'],
]);

it('compares accounts payment mode constants against the migrated mode', function (int $oldModeId, string $newMode): void {
    $action = app(CompareRentOutPopulationAction::class);

    $old = (fn () => $this->normaliseChildField($oldModeId, 'payment_mode', true, []))->call($action);
    $new = (fn () => $this->normaliseChildField($newMode, 'payment_mode', false, []))->call($action);

    expect($old)->toBe($new);
})->with([
    'cheque' => [2, 'cheque'],
    'pos' => [3, 'pos'],
    'bank transfer' => [4, 'bank_transfer'],
]);
