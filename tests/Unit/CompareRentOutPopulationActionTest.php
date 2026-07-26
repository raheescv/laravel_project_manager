<?php

use App\Actions\RentOut\Comparison\CompareRentOutPopulationAction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('builds both record links from configured site urls', function () {
    foreach (['comparison_old', 'comparison_new'] as $connection) {
        config()->set("database.connections.{$connection}", [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge($connection);
    }

    Schema::connection('comparison_old')->create('rentouts', function (Blueprint $table): void {
        $table->unsignedBigInteger('id')->primary();
        $table->string('agreement_type');
        $table->unsignedTinyInteger('status');
    });
    Schema::connection('comparison_new')->create('rent_outs', function (Blueprint $table): void {
        $table->unsignedBigInteger('id')->primary();
        $table->string('agreement_type');
        $table->string('status');
    });
    Schema::connection('comparison_new')->create('accounts', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('second_reference_no')->nullable();
    });

    DB::connection('comparison_old')->table('rentouts')->insert([
        'id' => 1887,
        'agreement_type' => 'lease',
        'status' => 1,
    ]);
    DB::connection('comparison_new')->table('rent_outs')->insert([
        'id' => 1887,
        'agreement_type' => 'lease',
        'status' => 'occupied',
    ]);
    config()->set('rentout-comparison.site_1_url', 'https://legacy.example.test/');
    config()->set('rentout-comparison.site_2_url', 'https://new.example.test/');

    $comparison = app(CompareRentOutPopulationAction::class)->execute(
        oldConnection: 'comparison_old',
        newConnection: 'comparison_new',
        ids: [1887],
    );

    expect($comparison['records'][1887])
        ->old_url->toBe('https://legacy.example.test/Property/lease/view/1887')
        ->new_url->toBe('https://new.example.test/property/sale/view/1887');
});
