<?php

use App\Actions\RentOut\Report\GetRoiReportRowsAction;
use App\Livewire\RentOut\Report\RoiTable;
use App\Models\RentOutRoi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * The ROI report collapses every ROI line onto its agreement and settles it
 * against the ROI payouts, mirroring the service charge report: the cases
 * worth pinning down are the aggregation, the cap on `paid`, and the
 * settlement states that follow.
 *
 * Everything is seeded under one throwaway project group and every assertion
 * filters on it, so the suite is unaffected by whatever else is in the database.
 */
const ROI_TENANT = 1;

const ROI_BRANCH = 1;

function roiLine(int $rentOutId, float $amount, string $start, string $remark = ''): void
{
    DB::table('rent_out_rois')->insert([
        'tenant_id' => ROI_TENANT,
        'branch_id' => ROI_BRANCH,
        'rent_out_id' => $rentOutId,
        'date' => $start,
        'start_date' => $start,
        'end_date' => '2026-12-31',
        'no_of_months' => 6,
        'sale_price' => 1000000,
        'per_month_roi' => $amount / 6,
        'amount' => $amount,
        'remark' => $remark,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function roiPayout(int $rentOutId, float $amount, string $date = '2026-06-01'): void
{
    DB::table('rent_out_transactions')->insert([
        'tenant_id' => ROI_TENANT,
        'branch_id' => ROI_BRANCH,
        'rent_out_id' => $rentOutId,
        'date' => $date,
        'credit' => 0,
        'debit' => $amount,
        'account_id' => 1,
        'source' => RentOutRoi::PAYMENT_SOURCE,
        'payment_type' => 'ROI',
    ]);
}

beforeEach(function () {
    $this->action = new GetRoiReportRowsAction();

    $suffix = 'roi-report-'.Str::random(8);
    $this->groupId = DB::table('property_groups')->insertGetId([
        'tenant_id' => ROI_TENANT, 'branch_id' => ROI_BRANCH, 'name' => "Group {$suffix}",
    ]);
    $buildingId = DB::table('property_buildings')->insertGetId([
        'tenant_id' => ROI_TENANT, 'branch_id' => ROI_BRANCH,
        'property_group_id' => $this->groupId, 'name' => "Tower {$suffix}",
    ]);
    $typeId = DB::table('property_types')->insertGetId([
        'tenant_id' => ROI_TENANT, 'name' => "Studio {$suffix}",
    ]);

    $agreement = function (string $unit) use ($buildingId, $typeId): int {
        $propertyId = DB::table('properties')->insertGetId([
            'tenant_id' => ROI_TENANT,
            'branch_id' => ROI_BRANCH,
            'property_group_id' => $this->groupId,
            'property_building_id' => $buildingId,
            'property_type_id' => $typeId,
            'number' => $unit,
            'ownership' => 'Owner',
        ]);

        return DB::table('rent_outs')->insertGetId([
            'tenant_id' => ROI_TENANT,
            'branch_id' => ROI_BRANCH,
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

    $this->partial = $agreement('9201');
    roiLine($this->partial, 1200, '2026-01-01', 'First half');
    roiLine($this->partial, 600, '2026-07-01', 'Second half');
    roiPayout($this->partial, 1000);

    $this->settled = $agreement('9202');
    roiLine($this->settled, 1200, '2026-01-01', 'Only period');
    roiPayout($this->settled, 5000);

    $this->unpaid = $agreement('9203');
    roiLine($this->unpaid, 900, '2026-01-01', 'Only period');

    $this->filters = fn (array $overrides = []) => array_merge([
        'filterGroup' => $this->groupId,
        'dateFrom' => '2026-01-01',
        'dateTo' => '2026-12-31',
    ], $overrides);
});

it('collapses an agreement\'s ROI lines onto one row', function () {
    $row = $this->action->rows(($this->filters)())->firstWhere('rent_out_id', $this->partial);

    expect((int) $row->entry_count)->toBe(2)
        ->and((float) $row->amount)->toBe(1800.0)
        ->and($row->period_start)->toBe('2026-01-01')
        ->and($row->period_end)->toBe('2026-12-31')
        ->and((int) $row->no_of_months)->toBe(12)
        ->and((float) $row->sale_price)->toBe(1000000.0)
        ->and($row->property_number)->toBe('9201')
        ->and($row->remark)->toBe('Multiple entries');
});

it('settles each agreement against its ROI payouts, capped at what was booked', function () {
    $rows = $this->action->rows(($this->filters)())->keyBy('rent_out_id');

    expect((float) $rows[$this->partial]->paid)->toBe(1000.0)
        ->and((float) $rows[$this->partial]->balance)->toBe(800.0)
        ->and($rows[$this->partial]->status)->toBe('partial')
        ->and((float) $rows[$this->settled]->payouts)->toBe(5000.0)
        ->and((float) $rows[$this->settled]->paid)->toBe(1200.0)
        ->and($rows[$this->settled]->status)->toBe('paid')
        ->and((float) $rows[$this->unpaid]->paid)->toBe(0.0)
        ->and($rows[$this->unpaid]->status)->toBe('unpaid');
});

it('ignores payouts made after the closing date', function () {
    roiPayout($this->unpaid, 900, '2027-03-01');

    $inWindow = $this->action->rows(($this->filters)())->firstWhere('rent_out_id', $this->unpaid);
    $later = $this->action->rows(($this->filters)(['dateTo' => '2027-12-31']))
        ->firstWhere('rent_out_id', $this->unpaid);

    expect((float) $inWindow->paid)->toBe(0.0)
        ->and((float) $later->paid)->toBe(900.0);
});

it('totals and breaks down over the settled rows', function () {
    $totals = $this->action->totals(($this->filters)());
    $summary = $this->action->summaryByGroup(($this->filters)());

    // 1,000 + 1,200 (capped, not 5,000) + 0 paid against 3,900 booked.
    expect($totals)->toMatchArray([
        'agreements' => 3, 'lines' => 4, 'amount' => 3900.0, 'paid' => 2200.0, 'balance' => 1700.0, 'paid_rate' => 56.4,
    ])
        ->and($summary)->toHaveCount(1)
        ->and($summary[0]['amount'])->toBe(3900.0)
        ->and($summary[0]['paid'])->toBe(2200.0);
});

it('filters by settlement state', function (string $status, int $expected) {
    expect($this->action->rows(($this->filters)(['filterStatus' => $status])))->toHaveCount($expected);
})->with([
    ['paid', 1],
    ['partial', 1],
    ['unpaid', 1],
    ['', 3],
]);

it('expands to ROI lines that add up to their agreement', function () {
    $lines = $this->action->lines(($this->filters)(), [$this->partial]);

    expect($lines[$this->partial])->toHaveCount(2)
        ->and((float) $lines[$this->partial]->sum('amount'))->toBe(1800.0);
});

it('renders the report screen for a user who can view ROI', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('rent out roi.view', 'web'));
    $this->actingAs($user);

    Livewire::test(RoiTable::class)
        ->set('dateFrom', '2026-01-01')
        ->set('dateTo', '2026-12-31')
        ->assertSee('ROI Booked')
        ->assertSee('ROI Breakdown by Project/Group')
        ->assertDontSee('Service Charge');

    $this->get(route('property::report::roi'))->assertOk()->assertSee('Sale ROI Report');
});

it('keeps the report behind the ROI view permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('property::report::roi'))->assertForbidden();
});
