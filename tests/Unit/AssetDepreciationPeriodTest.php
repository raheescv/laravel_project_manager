<?php

use App\Actions\Asset\GenerateDepreciationScheduleAction;
use App\Livewire\Product\Page;
use App\Models\Product;
use Carbon\Carbon;

it('calculates depreciation amount using the selected duration period', function (string $period, float $duration, float $expectedAmount, string $expectedLabel): void {
    $page = new Page();
    $page->type = 'asset';
    $page->products = [
        'cost' => 10000,
        'duration' => $duration,
        'duration_period' => $period,
        'depreciation_method' => 'straight_line',
        'declining_factor' => 2.0,
    ];

    $method = new ReflectionMethod(Page::class, 'recalculateDepreciation');
    $method->invoke($page);

    expect($page->products['depreciation_amount'])->toBe($expectedAmount)
        ->and($page->getDepreciationPreviewProperty()['amount_label'])->toBe($expectedLabel);
})->with([
    'daily' => ['days', 20, 500.0, 'Daily Depreciation'],
    'monthly' => ['months', 10, 1000.0, 'Monthly Depreciation'],
    'yearly' => ['years', 5, 2000.0, 'Yearly Depreciation'],
]);

it('generates schedule periods and dates from the selected duration period', function (): void {
    $action = new GenerateDepreciationScheduleAction();
    $startDate = Carbon::parse('2026-01-31');

    $getTotalPeriods = new ReflectionMethod(GenerateDepreciationScheduleAction::class, 'getTotalPeriods');
    $resolvePeriodType = new ReflectionMethod(GenerateDepreciationScheduleAction::class, 'resolvePeriodType');
    $resolveScheduleDate = new ReflectionMethod(GenerateDepreciationScheduleAction::class, 'resolveScheduleDate');

    expect($getTotalPeriods->invoke($action, new Product([
        'duration' => 5,
        'duration_period' => 'years',
    ])))->toBe(5)
        ->and($resolvePeriodType->invoke($action, 'days'))->toBe('daily')
        ->and($resolvePeriodType->invoke($action, 'months'))->toBe('monthly')
        ->and($resolvePeriodType->invoke($action, 'years'))->toBe('yearly')
        ->and($resolveScheduleDate->invoke($action, $startDate, 2, 'days')->toDateString())->toBe('2026-02-01')
        ->and($resolveScheduleDate->invoke($action, $startDate, 2, 'months')->toDateString())->toBe('2026-02-28')
        ->and($resolveScheduleDate->invoke($action, $startDate, 2, 'years')->toDateString())->toBe('2027-01-31');
});
