<?php

use App\Actions\RentOut\Comparison\CompareRentOutPopulationAction;
use App\Actions\RentOut\Comparison\StoreRentOutComparisonAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

use function Pest\Laravel\mock;

uses(TestCase::class);

it('registers the comparison command', function () {
    expect(collect(Artisan::all())->keys())->toContain('property:compare-rentout-migration');
});

it('stores live comparison data for selected ids', function () {
    $comparison = comparisonCommandFixture();
    $compare = mock(CompareRentOutPopulationAction::class);
    $compare->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($old, $new, $ids, $type, $chunk) => $old === 'mysql2'
            && $new === 'mysql'
            && $ids === [1887, 1835]
            && $type === null
            && $chunk === 250)
        ->andReturn($comparison);
    $store = mock(StoreRentOutComparisonAction::class);
    $store->shouldReceive('execute')
        ->once()
        ->with($comparison)
        ->andReturn(['stored' => 2, 'deleted' => 0]);

    $this->artisan('property:compare-rentout-migration', ['--ids' => '1887,1835'])
        ->expectsOutputToContain('Live comparison data refreshed successfully.')
        ->expectsOutputToContain('Stored: 2 rows')
        ->assertSuccessful();
});

it('fails when differences are found and requested', function () {
    $comparison = comparisonCommandFixture(differing: 1);
    mock(CompareRentOutPopulationAction::class)->shouldReceive('execute')->once()->andReturn($comparison);
    mock(StoreRentOutComparisonAction::class)->shouldReceive('execute')->once()->andReturn(['stored' => 2, 'deleted' => 0]);

    $this->artisan('property:compare-rentout-migration', ['--fail-on-difference' => true])
        ->assertExitCode(Command::FAILURE);
});

it('rejects invalid agreement types before querying databases', function () {
    mock(CompareRentOutPopulationAction::class)->shouldNotReceive('execute');

    $this->artisan('property:compare-rentout-migration', ['--type' => 'commercial'])
        ->expectsOutputToContain('must be rental or lease')
        ->assertExitCode(Command::INVALID);
});

function comparisonCommandFixture(int $differing = 0): array
{
    return [
        'meta' => [
            'generated_at' => '2026-07-26T12:00:00+05:30',
            'old_connection' => 'mysql2',
            'new_connection' => 'mysql',
            'type' => null,
            'selected_ids' => [1887, 1835],
            'version' => 1,
        ],
        'summary' => [
            'total' => 2,
            'matching' => 2 - $differing,
            'differing' => $differing,
            'missing' => 0,
            'extra' => 0,
            'match_percentage' => $differing ? 50.0 : 100.0,
            'by_category' => ['Rental agreement' => 2],
        ],
        'records' => [],
    ];
}
