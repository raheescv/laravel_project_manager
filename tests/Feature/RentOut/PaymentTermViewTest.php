<?php

use App\Actions\RentOut\Payment\ReverseTransactionAction;
use App\Helpers\RentOutTransactionHelper;
use App\Livewire\RentOut\Tabs\PaymentTermsTab;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * A payment term's view page lists the journals its receipts posted and the
 * audit trail of the term, its receipts and their journals — the Payment
 * Terms tab links to it from each row.
 */
beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    app(TenantService::class)->setCurrentTenant(Tenant::find($this->tenantId));
    session(['branch_id' => 1]);

    $account = fn (string $name, string $type) => DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => $name.' '.Str::random(6), 'account_type' => $type,
    ]);

    $this->customerId = $account('Term Customer', 'asset');
    $this->cashId = $account('Term Cash', 'asset');

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
        'number' => 'PT-501',
        'ownership' => 'Owner',
    ]);

    $this->makeRentOut = fn (string $agreementType) => DB::table('rent_outs')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_id' => $propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'account_id' => $this->customerId,
        'agreement_type' => $agreementType,
        'status' => 'occupied',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $this->rentOutId = ($this->makeRentOut)('lease');

    $this->term = RentOutPaymentTerm::create([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'rent_out_id' => $this->rentOutId,
        'label' => 'installment',
        'amount' => 7700,
        'discount' => 0,
        'due_date' => '2026-02-01',
    ]);

    $this->grant = fn (string ...$permissions) => auth()->user()->givePermissionTo(
        collect($permissions)->map(fn ($name) => Permission::findOrCreate($name, 'web'))
    );
});

/**
 * A bare localhost URL: the app's test host maps to a tenant by domain, which
 * would override the signed-in user's tenant and hide every record here.
 */
function termUrl(string $module, int $termId): string
{
    return 'http://localhost'.route("property::{$module}::payment-term", $termId, false);
}

function payTerm(RentOutPaymentTerm $term, float $amount, int $accountId): RentOutTransaction
{
    $term->paid = (float) $term->paid + $amount;
    $term->save();

    $response = (new RentOutTransactionHelper())->storeRentPayment($term->rent_out_id, $term, $amount, $accountId, '2026-02-01', 'Feb installment');
    expect($response['success'])->toBeTrue();

    return $response['data'];
}

it('shows the journal entries and audit trail of a paid term', function () {
    ($this->grant)('rent out lease.view', 'rent out lease.view journal entries');
    $payment = payTerm($this->term, 7700, $this->cashId);

    $this->get(termUrl('sale', $this->term->id))
        ->assertOk()
        ->assertSee('Payment Term')
        ->assertSee('Journal Entries')
        ->assertSee('Journal #'.$payment->journal_id)
        ->assertSee('Total (Active)')
        ->assertSee('Audit Trail')
        ->assertSee('ptv-audit-journals', false);
});

it('marks the journal of a reversed receipt as reversed', function () {
    ($this->grant)('rent out lease.view', 'rent out lease.view journal entries');
    $payment = payTerm($this->term, 7700, $this->cashId);

    (new ReverseTransactionAction())->reverseForTerm($this->term->fresh());

    $this->get(termUrl('sale', $this->term->id))
        ->assertOk()
        ->assertSee('Journal #'.$payment->journal_id)
        ->assertSee('1 reversed')
        ->assertDontSee('Total (Active)');
});

it('hides journals from users without the journal permission', function () {
    ($this->grant)('rent out lease.view');
    $payment = payTerm($this->term, 7700, $this->cashId);

    $this->get(termUrl('sale', $this->term->id))
        ->assertOk()
        ->assertSee('Audit Trail')
        ->assertDontSee('Journal #'.$payment->journal_id)
        ->assertDontSee('ptv-audit-journals', false);
});

it('does not open a rental term through the sale route', function () {
    ($this->grant)('rent out lease.view', 'rent out.view');
    $rentalTerm = RentOutPaymentTerm::create([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'rent_out_id' => ($this->makeRentOut)('rental'),
        'amount' => 1000,
        'due_date' => '2026-02-01',
    ]);

    $this->get(termUrl('sale', $rentalTerm->id))->assertNotFound();
    $this->get(termUrl('rent', $rentalTerm->id))->assertOk();
});

it('links each term row to its journal and audit view', function () {
    ($this->grant)('rent out lease.view journal entries');
    $url = route('property::sale::payment-term', $this->term->id);

    Livewire::test(PaymentTermsTab::class, ['rentOutId' => $this->rentOutId, 'isRental' => false])
        ->assertSeeHtml($url.'#journals')
        ->assertSeeHtml($url.'#audit');
});

it('omits the journal link without the journal permission', function () {
    $url = route('property::sale::payment-term', $this->term->id);

    Livewire::test(PaymentTermsTab::class, ['rentOutId' => $this->rentOutId, 'isRental' => false])
        ->assertDontSeeHtml($url.'#journals')
        ->assertSeeHtml($url.'#audit');
});
