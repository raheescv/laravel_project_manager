<?php

use App\Livewire\Report\Sale\OverviewReport;
use Livewire\Livewire;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $world = PosWorld::create();
    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);
});

it('keeps date and branch selections as drafts until the report is fetched', function (): void {
    $today = date('Y-m-d');

    Livewire::test(OverviewReport::class)
        ->assertSeeHtml('data-testid="overview-report-submit"')
        ->set('filter_from_date', '2026-01-01')
        ->set('filter_to_date', '2026-01-31')
        ->set('filter_branch_id', 7)
        ->assertSet('fromDate', $today)
        ->assertSet('toDate', $today)
        ->call('fetchReport')
        ->assertHasNoErrors()
        ->assertSet('fromDate', '2026-01-01')
        ->assertSet('toDate', '2026-01-31')
        ->assertSet('branchId', 7);
});

it('rejects a to date before the from date', function (): void {
    $today = date('Y-m-d');

    Livewire::test(OverviewReport::class)
        ->set('filter_from_date', '2026-02-10')
        ->set('filter_to_date', '2026-02-01')
        ->call('fetchReport')
        ->assertHasErrors(['filter_to_date' => 'after_or_equal'])
        ->assertSet('fromDate', $today)
        ->assertSet('toDate', $today);
});
