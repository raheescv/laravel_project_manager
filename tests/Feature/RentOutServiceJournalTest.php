<?php

use App\Helpers\RentOutTransactionHelper;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A rent-out service charge is billed to the customer and credited to income.
 * The bug these tests pin down is a charge posting both legs against the same
 * account — the customer on either side — which nets to nothing and never
 * recognises the revenue the category select was chosen for.
 */

/** Both legs of a transaction's journal, debit first. */
function rsjEntries(int $paymentId): array
{
    $journalId = DB::table('rent_out_transactions')->where('id', $paymentId)->value('journal_id');

    return JournalEntry::withoutGlobalScopes()
        ->whereNull('deleted_at')   // withoutGlobalScopes() also drops the soft-delete scope
        ->where('journal_id', $journalId)
        ->orderByDesc('debit')
        ->get()
        ->all();
}

beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    session(['branch_id' => 1]);

    $account = fn (string $name, string $type) => DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => $name.' '.Str::random(6), 'account_type' => $type,
    ]);

    $this->customerId = $account('Owner Unit', 'asset');
    $this->categoryId = $account('Consultancy Fees', 'income');
    $this->cardId = $account('Card', 'asset');

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
        'number' => '9201',
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

    $this->helper = new RentOutTransactionHelper();
    $this->form = [
        'date' => '2026-08-13',
        'amount' => 250,
        'category' => (string) $this->categoryId,
        'account_id' => (string) $this->cardId,
        'remark' => 'Consultancy',
    ];
});

it('bills a pay-later service to the customer and credits the chosen category', function () {
    $response = $this->helper->storeServicePayLater($this->rentOutId, $this->form);

    expect($response['success'])->toBeTrue();

    [$debit, $credit] = rsjEntries($response['data']->id);

    expect((int) $debit->account_id)->toBe($this->customerId)
        ->and((float) $debit->debit)->toBe(250.0)
        ->and((int) $credit->account_id)->toBe($this->categoryId)
        ->and((float) $credit->credit)->toBe(250.0)
        // Each leg names the other, and the two are never the same account.
        ->and((int) $debit->counter_account_id)->toBe($this->categoryId)
        ->and((int) $credit->counter_account_id)->toBe($this->customerId);
});

it('settles the receivable on a pay-now service instead of booking the income twice', function () {
    $response = $this->helper->storeServicePayNow($this->rentOutId, $this->form);

    expect($response['success'])->toBeTrue();

    $rows = DB::table('rent_out_transactions')->where('rent_out_id', $this->rentOutId)->orderBy('id')->get();
    expect($rows)->toHaveCount(2);

    // Leg 1 recognises the income: Dr Customer, Cr Category.
    [$chargeDebit, $chargeCredit] = rsjEntries($rows[0]->id);
    expect((int) $chargeDebit->account_id)->toBe($this->customerId)
        ->and((int) $chargeCredit->account_id)->toBe($this->categoryId);

    // Leg 2 only collects it: Dr Card, Cr Customer. Crediting income here as
    // well would recognise the same revenue twice.
    [$receiptDebit, $receiptCredit] = rsjEntries($rows[1]->id);
    expect((int) $receiptDebit->account_id)->toBe($this->cardId)
        ->and((int) $receiptCredit->account_id)->toBe($this->customerId);

    $incomeCredited = JournalEntry::withoutGlobalScopes()
        ->whereIn('journal_id', $rows->pluck('journal_id'))
        ->where('account_id', $this->categoryId)
        ->sum('credit');
    expect((float) $incomeCredited)->toBe(250.0);
});

it('moves the entries when the category on a charge is changed', function () {
    $charge = $this->helper->storeServicePayLater($this->rentOutId, $this->form)['data'];

    $otherCategoryId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Maintenance Fees '.Str::random(6), 'account_type' => 'income',
    ]);

    $response = $this->helper->update($charge->id, [
        'date' => '2026-08-14',
        'amount' => 400,
        'category' => (string) $otherCategoryId,
        'account_id' => (string) $this->cardId,
        'remark' => 'Recategorised',
    ]);

    expect($response['success'])->toBeTrue();

    $entries = rsjEntries($charge->id);
    expect($entries)->toHaveCount(2);

    [$debit, $credit] = $entries;
    expect((int) $debit->account_id)->toBe($this->customerId)
        ->and((float) $debit->debit)->toBe(400.0)
        ->and((int) $credit->account_id)->toBe($otherCategoryId)
        ->and((float) $credit->credit)->toBe(400.0);

    // The charge row stays the customer's receivable; the payment mode on the
    // modal belongs to the receipt row, not this one.
    expect((int) $response['data']->account_id)->toBe($this->customerId);
});

it('refuses to post when no income account can be resolved', function () {
    // A non-numeric category with no locked income account for the tenant
    // leaves nothing to credit — failing beats posting the customer against
    // itself, which is what the old code did.
    $response = $this->helper->storeServicePayLater(
        $this->rentOutId,
        array_merge($this->form, ['category' => 'management_fee'])
    );

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('single account')
        ->and(JournalEntry::withoutGlobalScopes()->count())->toBe(0);
});
