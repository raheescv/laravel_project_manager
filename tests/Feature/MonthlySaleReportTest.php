<?php

use App\Livewire\Report\Sale\MonthlySaleReport;
use Livewire\Livewire;

it('keeps filter selections as drafts until the report is submitted', function (): void {
    $currentYear = (int) date('Y');

    Livewire::test(MonthlySaleReport::class)
        ->assertSee('Monthly sales performance')
        ->assertSeeHtml('data-testid="monthly-report-submit"')
        ->set('filter_from_month', '01')
        ->set('filter_to_month', '03')
        ->assertSet('from_month', date('m'))
        ->call('fetchReport')
        ->assertHasNoErrors()
        ->assertSet('from_year', $currentYear)
        ->assertSet('from_month', '01')
        ->assertSet('to_month', '03');
});

it('rejects a report period whose end month is before its start month', function (): void {
    Livewire::test(MonthlySaleReport::class)
        ->set('filter_from_month', '12')
        ->set('filter_to_month', '01')
        ->call('fetchReport')
        ->assertHasErrors(['filter_to_month'])
        ->assertSet('from_month', date('m'));
});

it('shows responsive value labels above the gross and net sale bars', function (): void {
    $reportView = file_get_contents(resource_path('views/livewire/report/sale/monthly-sale-report.blade.php'));

    expect($reportView)
        ->toContain("id: 'monthlyBarValueLabels'")
        ->toContain("dataset.type !== 'bar'")
        ->toContain('chart.data.labels.length > 6 ? -60 : -35')
        ->toContain('chart.ctx.rotate(labelAngle * Math.PI / 180)')
        ->toContain('chart.ctx.fillText(fmtCompact.format(value), 0, 0)');
});
