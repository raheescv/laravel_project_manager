<?php

use App\Actions\RentOut\Comparison\StoreRentOutComparisonAction;
use App\Models\RentOutComparison;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('database.default', 'comparison_testing');
    config()->set('database.connections.comparison_testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    DB::purge('comparison_testing');

    Schema::connection('comparison_testing')->create('rent_out_comparisons', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('rent_out_id')->unique();
        $table->string('agreement_type');
        $table->string('category');
        $table->string('status')->nullable();
        $table->boolean('is_booking');
        $table->boolean('exists_old');
        $table->boolean('exists_new');
        $table->boolean('matches');
        $table->unsignedInteger('difference_count');
        $table->text('old_url');
        $table->text('new_url');
        $table->json('payload');
        $table->timestamp('compared_at');
        $table->timestamp('verified_at')->nullable();
        $table->unsignedBigInteger('verified_by')->nullable();
        $table->timestamps();
    });
});

it('stores current records, removes stale records, and preserves verification', function () {
    RentOutComparison::query()->create(comparisonStoredRow([
        'rent_out_id' => 10,
        'verified_at' => '2026-07-25 10:00:00',
        'verified_by' => 7,
    ]));
    RentOutComparison::query()->create(comparisonStoredRow(['rent_out_id' => 99]));

    $result = app(StoreRentOutComparisonAction::class)->execute(comparisonStorageFixture());

    expect($result)->toBe(['stored' => 2, 'deleted' => 1])
        ->and(RentOutComparison::query()->pluck('rent_out_id')->all())->toBe([10, 20])
        ->and(RentOutComparison::query()->where('rent_out_id', 10)->value('verified_by'))->toBe(7)
        ->and(RentOutComparison::query()->where('rent_out_id', 10)->value('difference_count'))->toBe(2);
});

function comparisonStoredRow(array $overrides = []): array
{
    return array_merge([
        'rent_out_id' => 1,
        'agreement_type' => 'rental',
        'category' => 'Rental agreement',
        'status' => 'occupied',
        'is_booking' => false,
        'exists_old' => true,
        'exists_new' => true,
        'matches' => false,
        'difference_count' => 1,
        'old_url' => 'https://site-one.test/Property/Rentout/view/1',
        'new_url' => 'https://site-two.test/property/rent/view/1',
        'payload' => ['header' => [], 'tabs' => [], 'ledger' => []],
        'compared_at' => '2026-07-26 12:00:00',
        'verified_at' => null,
        'verified_by' => null,
    ], $overrides);
}

function comparisonStorageFixture(): array
{
    $records = [];
    foreach ([10, 20] as $id) {
        $records[$id] = [
            'id' => $id,
            'agreement_type' => 'rental',
            'category' => 'Rental agreement',
            'status' => 'occupied',
            'is_booking' => false,
            'exists_old' => true,
            'exists_new' => true,
            'matches' => false,
            'difference_count' => 2,
            'old_url' => "https://site-one.test/Property/Rentout/view/{$id}",
            'new_url' => "https://site-two.test/property/rent/view/{$id}",
            'header' => [],
            'tabs' => [],
            'ledger' => [],
        ];
    }

    return [
        'meta' => [
            'generated_at' => '2026-07-26T12:00:00+05:30',
            'selected_ids' => [],
            'type' => null,
        ],
        'records' => $records,
    ];
}
