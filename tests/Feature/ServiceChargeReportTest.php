<?php

use App\Actions\RentOut\Report\GetServiceChargeReportRowsAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The report collapses every service charge line onto its agreement and settles
 * it against the service receipts, so the cases worth pinning down are the
 * aggregation itself, the cap on `paid`, and the settlement states that follow.
 *
 * Everything is seeded under one throwaway project group and every assertion
 * filters on it, so the suite is unaffected by whatever else is in the database.
 */
const SVC_TENANT = 1;

const SVC_BRANCH = 1;

function svcCharge(int $rentOutId, float $amount, string $start, string $remark = ''): void
{
    DB::table('rent_out_services')->insert([
        'tenant_id' => SVC_TENANT,
        'branch_id' => SVC_BRANCH,
        'rent_out_id' => $rentOutId,
        'name' => 'Service Charge',
        'amount' => $amount,
        'start_date' => $start,
        'end_date' => '2026-12-31',
        'no_of_months' => 12,
        'no_of_days' => 365,
        'remark' => $remark,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function svcReceipt(int $rentOutId, float $amount, string $date = '2026-06-01'): void
{
    DB::table('rent_out_transactions')->insert([
        'tenant_id' => SVC_TENANT,
        'branch_id' => SVC_BRANCH,
        'rent_out_id' => $rentOutId,
        'date' => $date,
        'credit' => $amount,
        'debit' => 0,
        'source' => 'ServiceCharge',
        'payment_type' => 'Services',
    ]);
}

beforeEach(function () {
    $this->action = new GetServiceChargeReportRowsAction();

    $suffix = 'svc-report-'.Str::random(8);
    $this->groupId = DB::table('property_groups')->insertGetId([
        'tenant_id' => SVC_TENANT, 'branch_id' => SVC_BRANCH, 'name' => "Group {$suffix}",
    ]);
    $buildingId = DB::table('property_buildings')->insertGetId([
        'tenant_id' => SVC_TENANT, 'branch_id' => SVC_BRANCH,
        'property_group_id' => $this->groupId, 'name' => "Tower {$suffix}",
    ]);
    $typeId = DB::table('property_types')->insertGetId([
        'tenant_id' => SVC_TENANT, 'name' => "Studio {$suffix}",
    ]);

    $agreement = function (string $unit) use ($buildingId, $typeId): int {
        $propertyId = DB::table('properties')->insertGetId([
            'tenant_id' => SVC_TENANT,
            'branch_id' => SVC_BRANCH,
            'property_group_id' => $this->groupId,
            'property_building_id' => $buildingId,
            'property_type_id' => $typeId,
            'number' => $unit,
            'ownership' => 'Owner',
        ]);

        return DB::table('rent_outs')->insertGetId([
            'tenant_id' => SVC_TENANT,
            'branch_id' => SVC_BRANCH,
            'property_id' => $propertyId,
            'property_building_id' => $buildingId,
            'property_type_id' => $typeId,
            'property_group_id' => $this->groupId,
            'account_id' => 1,
            'agreement_type' => 'lease',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    };

    $this->partial = $agreement('9101');
    svcCharge($this->partial, 1000, '2026-01-01', 'First period');
    svcCharge($this->partial, 500, '2026-07-01', 'Top up');
    svcReceipt($this->partial, 900);

    $this->settled = $agreement('9102');
    svcCharge($this->settled, 1000, '2026-01-01', 'Only period');
    svcReceipt($this->settled, 5000);

    $this->unpaid = $agreement('9103');
    svcCharge($this->unpaid, 800, '2026-01-01', 'Only period');

    $this->filters = fn (array $overrides = []) => array_merge([
        'filterGroup' => $this->groupId,
        'dateFrom' => '2026-01-01',
        'dateTo' => '2026-12-31',
    ], $overrides);
});

it('collapses an agreement\'s charge lines onto one row', function () {
    $row = $this->action->rows(($this->filters)())->firstWhere('rent_out_id', $this->partial);

    expect((int) $row->charge_count)->toBe(2)
        ->and((float) $row->amount)->toBe(1500.0)
        ->and($row->period_start)->toBe('2026-01-01')
        ->and($row->period_end)->toBe('2026-12-31')
        ->and((int) $row->no_of_months)->toBe(24)
        ->and($row->property_number)->toBe('9101')
        // The two lines carry different remarks, so neither may stand in for the agreement.
        ->and($row->remark)->toBe('Multiple entries');
});

it('settles each agreement against its service receipts', function () {
    $rows = $this->action->rows(($this->filters)())->keyBy('rent_out_id');

    expect((float) $rows[$this->partial]->paid)->toBe(900.0)
        ->and((float) $rows[$this->partial]->balance)->toBe(600.0)
        ->and($rows[$this->partial]->status)->toBe('partial')
        ->and((float) $rows[$this->unpaid]->paid)->toBe(0.0)
        ->and($rows[$this->unpaid]->status)->toBe('unpaid');
});

it('never counts more paid than it charged', function () {
    $row = $this->action->rows(($this->filters)())->firstWhere('rent_out_id', $this->settled);

    // 5,000 banked against a 1,000 charge must not read as a 4,000 credit balance.
    expect((float) $row->receipts)->toBe(5000.0)
        ->and((float) $row->paid)->toBe(1000.0)
        ->and((float) $row->balance)->toBe(0.0)
        ->and($row->status)->toBe('paid');
});

it('ignores receipts banked after the closing date', function () {
    svcReceipt($this->unpaid, 800, '2027-03-01');

    $inWindow = $this->action->rows(($this->filters)())->firstWhere('rent_out_id', $this->unpaid);
    $later = $this->action->rows(($this->filters)(['dateTo' => '2027-12-31']))
        ->firstWhere('rent_out_id', $this->unpaid);

    expect((float) $inWindow->paid)->toBe(0.0)
        ->and((float) $later->paid)->toBe(800.0);
});

it('totals over the settled rows rather than the raw receipts', function () {
    $totals = $this->action->totals(($this->filters)());

    expect($totals['agreements'])->toBe(3)
        ->and($totals['lines'])->toBe(4)
        ->and($totals['amount'])->toBe(3300.0)
        // 900 + 1,000 (capped, not 5,000) + 0
        ->and($totals['paid'])->toBe(1900.0)
        ->and($totals['balance'])->toBe(1400.0)
        ->and($totals['collection_rate'])->toBe(57.6);
});

it('breaks the same numbers down by project group', function () {
    $summary = $this->action->summaryByGroup(($this->filters)());

    expect($summary)->toHaveCount(1)
        ->and($summary[0]['agreements'])->toBe(3)
        ->and($summary[0]['lines'])->toBe(4)
        ->and($summary[0]['amount'])->toBe(3300.0)
        ->and($summary[0]['paid'])->toBe(1900.0)
        ->and($summary[0]['balance'])->toBe(1400.0);
});

it('filters by settlement state', function (string $status, int $expected) {
    expect($this->action->rows(($this->filters)(['filterStatus' => $status])))->toHaveCount($expected);
})->with([
    ['paid', 1],
    ['partial', 1],
    ['unpaid', 1],
    ['', 3],
]);

it('expands to charge lines that add up to their agreement', function () {
    $lines = $this->action->lines(($this->filters)(), [$this->partial]);

    expect($lines[$this->partial])->toHaveCount(2)
        ->and((float) $lines[$this->partial]->sum('amount'))->toBe(1500.0);
});

it('matches charges on the period they cover, not the day they were raised', function () {
    // The July top-up sits outside a first-half window, so only the January line counts.
    $row = $this->action->rows(($this->filters)(['dateTo' => '2026-06-30']))
        ->firstWhere('rent_out_id', $this->partial);

    expect((int) $row->charge_count)->toBe(1)
        ->and((float) $row->amount)->toBe(1000.0);
});

it('paginates the grouped rows', function () {
    $page = $this->action->paginate(($this->filters)(['sortField' => 'amount', 'sortDirection' => 'desc']), 2);

    expect($page->total())->toBe(3)
        ->and($page->count())->toBe(2)
        ->and((float) $page->first()->amount)->toBe(1500.0);
});
