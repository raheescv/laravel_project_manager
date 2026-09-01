<?php

use App\Livewire\SaleDaySession\SaleDaySessionsReport;
use App\Models\SaleDaySession;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * /sale/day-sessions-report — the compact report with the quick-range filter bar.
 * These pin the filter mechanics: presets move both dates, editing a date by
 * hand flips the chip to "custom", the status segment narrows the table while
 * the KPI split still counts both, and reset returns to the 30-day default.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);

    $tenant = $this->world->tenant->id;
    $branch = $this->world->branch->id;
    $user = $this->world->user->id;

    $this->closed = SaleDaySession::create([
        'tenant_id' => $tenant,
        'branch_id' => $branch,
        'opened_by' => $user,
        'closed_by' => $user,
        'opened_at' => Carbon::today()->subDays(3)->setTime(9, 0),
        'closed_at' => Carbon::today()->subDays(3)->setTime(21, 30),
        'opening_amount' => 100,
        'expected_amount' => 350,
        'closing_amount' => 340,
        'status' => 'closed',
    ]);

    $this->open = SaleDaySession::create([
        'tenant_id' => $tenant,
        'branch_id' => $branch,
        'opened_by' => $user,
        'opened_at' => Carbon::today()->setTime(9, 15),
        'opening_amount' => 100,
        'expected_amount' => 100,
        'status' => 'open',
    ]);
});

it('renders the report with the 30-day default and both sessions', function (): void {
    Livewire::test(SaleDaySessionsReport::class)
        ->assertOk()
        ->assertSet('preset', '30d')
        ->assertSet('dateFrom', Carbon::today()->subDays(30)->toDateString())
        ->assertSet('dateTo', Carbon::today()->toDateString())
        ->assertSee('Main Branch')
        ->assertSeeHtml('class="pill pill--green"')
        ->assertSeeHtml('class="pill pill--muted"')
        ->assertSee('Showing 1–2 of 2');
});

it('narrows the range with a quick preset and flips to custom when a date is edited', function (): void {
    Livewire::test(SaleDaySessionsReport::class)
        ->call('setRange', 'today')
        ->assertSet('preset', 'today')
        ->assertSet('dateFrom', Carbon::today()->toDateString())
        ->assertSee('Showing 1–1 of 1')
        ->set('dateFrom', Carbon::today()->subDays(10)->toDateString())
        ->assertSet('preset', 'custom')
        ->assertSee('Showing 1–2 of 2')
        ->call('setRange', 'nonsense')
        ->assertSet('preset', 'custom');
});

it('filters the table by status while the summary keeps the open/closed split', function (): void {
    Livewire::test(SaleDaySessionsReport::class)
        ->call('setStatus', 'closed')
        ->assertSet('status', 'closed')
        ->assertSee('Showing 1–1 of 1')
        ->assertDontSeeHtml('class="pill pill--green"')
        ->assertSeeHtml('<b>1</b> open')
        ->assertSeeHtml('<b>1</b> closed')
        ->call('setStatus', 'bogus')
        ->assertSet('status', 'closed');
});

it('shows the drawer variance for closed sessions only', function (): void {
    Livewire::test(SaleDaySessionsReport::class)
        ->assertSeeHtml('class="diff is-short"')
        ->assertSee(currency(-10));
});

it('sorts only on whitelisted columns and resets back to the defaults', function (): void {
    Livewire::test(SaleDaySessionsReport::class)
        ->call('sortBy', 'difference_amount')
        ->assertSet('sortField', 'difference_amount')
        ->assertSet('sortDirection', 'asc')
        ->call('sortBy', 'difference_amount')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'tenant_id')
        ->assertSet('sortField', 'difference_amount')
        ->call('setStatus', 'open')
        ->call('setRange', 'this_month')
        ->set('branchId', (string) $this->world->branch->id)
        ->assertSee('Reset')
        ->call('resetFilters')
        ->assertSet('status', '')
        ->assertSet('branchId', '')
        ->assertSet('preset', '30d')
        ->assertOk();
});

it('accepts only the offered page sizes', function (): void {
    Livewire::test(SaleDaySessionsReport::class)
        ->set('perPage', 10)
        ->assertOk()
        ->set('perPage', 100000)
        ->assertOk()
        ->assertSee('Showing 1–2 of 2');
});
