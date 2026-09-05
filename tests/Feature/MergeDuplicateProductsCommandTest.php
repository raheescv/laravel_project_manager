<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;

it('merges duplicates, repoints references and frees the name', function () {
    $tenantId = Product::query()->value('tenant_id') ?? 1;

    $keep = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId, 'name' => 'MERGE TEST A', 'type' => 'product',
        'code' => 'MT-A', 'unit_id' => 1, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $dupe = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId, 'name' => 'MERGE TEST B', 'type' => 'product',
        'code' => 'MT-B', 'unit_id' => 1, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $inventory = DB::table('inventories')->insertGetId([
        'tenant_id' => $tenantId, 'branch_id' => 1, 'product_id' => $dupe,
        'quantity' => 7, 'cost' => 100,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Both products carry a unit conversion for the same sub unit: only one may survive.
    DB::table('product_units')->insert([
        ['tenant_id' => $tenantId, 'product_id' => $keep, 'sub_unit_id' => 2, 'conversion_factor' => 10, 'created_at' => now(), 'updated_at' => now()],
        ['tenant_id' => $tenantId, 'product_id' => $dupe, 'sub_unit_id' => 2, 'conversion_factor' => 99, 'created_at' => now(), 'updated_at' => now()],
        ['tenant_id' => $tenantId, 'product_id' => $dupe, 'sub_unit_id' => 3, 'conversion_factor' => 5, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->artisan("app:merge-duplicate-products {$keep} {$dupe} --name=\"MERGE TEST\" --apply")
        ->expectsConfirmation('This permanently deletes the duplicate products. Continue?', 'yes')
        ->assertSuccessful();

    expect(DB::table('inventories')->where('id', $inventory)->value('product_id'))->toBe($keep)
        ->and(DB::table('products')->where('id', $dupe)->exists())->toBeFalse()
        ->and(DB::table('products')->where('id', $keep)->value('name'))->toBe('MERGE TEST');

    $units = DB::table('product_units')->where('product_id', $keep)->pluck('conversion_factor', 'sub_unit_id');
    expect($units)->toHaveCount(2)
        ->and((float) $units[2])->toBe(10.0)   // survivor's own row wins the collision
        ->and((float) $units[3])->toBe(5.0);   // non-colliding row moves across
});

it('refuses to merge products of different types', function () {
    $tenantId = Product::query()->value('tenant_id') ?? 1;

    $keep = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId, 'name' => 'TYPE TEST A', 'type' => 'product',
        'code' => 'TT-A', 'unit_id' => 1, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $dupe = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId, 'name' => 'TYPE TEST B', 'type' => 'service',
        'code' => 'TT-B', 'unit_id' => 1, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan("app:merge-duplicate-products {$keep} {$dupe}")->assertFailed();

    expect(DB::table('products')->where('id', $dupe)->exists())->toBeTrue();
});
