<?php

use App\Livewire\RentOut\Comparison\Dashboard;
use App\Models\RentOutComparison;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('database.default', 'comparison_dashboard_testing');
    config()->set('database.connections.comparison_dashboard_testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    DB::purge('comparison_dashboard_testing');

    Schema::connection('comparison_dashboard_testing')->create('rent_out_comparisons', function (Blueprint $table): void {
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

    $this->user = new User();
    $this->user->id = 7;
});

it('requires authentication for the live comparison page', function () {
    $route = Route::getRoutes()->getByName('property::rentout-comparison');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('auth');
});

it('filters verified records and persists verification in the table', function () {
    $first = RentOutComparison::query()->create(comparisonDashboardRow([
        'rent_out_id' => 11,
    ]));
    RentOutComparison::query()->create(comparisonDashboardRow([
        'rent_out_id' => 20,
        'verified_at' => '2026-07-26 10:00:00',
        'verified_by' => 7,
    ]));
    RentOutComparison::query()->create(comparisonDashboardRow([
        'rent_out_id' => 30,
        'matches' => true,
        'difference_count' => 0,
    ]));

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('#11')
        ->assertDontSee('#20')
        ->assertDontSee('#30')
        ->call('toggleVerified', $first->id)
        ->assertDontSee('#11');

    expect($first->fresh()->verified_by)->toBe(7)
        ->and($first->fresh()->verified_at)->not->toBeNull();
});

function comparisonDashboardRow(array $overrides = []): array
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
