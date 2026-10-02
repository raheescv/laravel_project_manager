<?php

use App\Livewire\Report\DayBookColumnVisibility;
use App\Livewire\Report\DayBookReport;
use Livewire\Livewire;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('shows and hides day book columns from the column visibility panel', function (): void {
    $report = Livewire::test(DayBookReport::class)
        ->assertSeeHtml("sortBy('journal_entries.journal_remarks')")
        ->assertSeeHtml('data-bs-target="#dayBookColumnVisibility"')
        ->assertViewHas('visibleColumns', fn (array $columns) => $columns['journal_remarks'] && $columns['debit']);

    Livewire::test(DayBookColumnVisibility::class)
        ->call('toggleColumn', 'journal_remarks')
        ->call('toggleColumn', 'debit')
        ->assertDispatched('DayBook-Refresh-Component');

    $report->call('$refresh')
        ->assertDontSeeHtml("sortBy('journal_entries.journal_remarks')")
        ->assertViewHas('visibleColumns', fn (array $columns) => ! $columns['journal_remarks'] && ! $columns['debit'] && $columns['credit'])
        ->assertSeeHtml('<th colspan="6" class="ps-3">');

    Livewire::test(DayBookColumnVisibility::class)->call('resetToDefaults');

    expect(DayBookColumnVisibility::current())
        ->journal_remarks->toBeTrue()
        ->debit->toBeTrue();
});
