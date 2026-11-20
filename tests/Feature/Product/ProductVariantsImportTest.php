<?php

use App\Imports\ProductImport;
use App\Imports\SaleImport;
use App\Models\Product;
use App\Notifications\ImportErrorsNotification;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\PosWorld;

/**
 * Size / colour variants share their family's name, so imports that find a
 * product by name must never silently pick a random sibling.
 */
beforeEach(function (): void {
    Queue::fake();
    Notification::fake();
    Storage::fake('public');
    $this->world = PosWorld::create();
    TenantCache::forget('branches');
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black', 'cost' => 85, 'barcode_number' => '62910044101']);
    $this->blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['barcode_number' => '62910044202']);
});

function importVariantRows(PosWorld $world, array $rows): void
{
    $path = tempnam(sys_get_temp_dir(), 'variant-import-').'.csv';
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    $mappings = array_combine($rows[0], $rows[0]);
    Excel::import(new ProductImport($world->user->id, count($rows) - 1, $world->branch->id, $mappings, 'product', 'Product', $world->tenant->id, 'update'), $path);
    @unlink($path);
}

it('updates the sibling the row\'s size and colour point at', function (): void {
    importVariantRows($this->world, [
        ['name', 'size', 'color', 'cost', 'mrp'],
        ['Running Shoe', '42', 'blue', '99', '150'],
    ]);

    expect((float) $this->blue->fresh()->cost)->toBe(99.0)
        ->and((float) $this->shoe->fresh()->cost)->toBe(85.0)
        ->and(Product::where('name', 'Running Shoe')->count())->toBe(2);
});

it('updates the sibling the row\'s barcode points at', function (): void {
    importVariantRows($this->world, [
        ['name', 'barcode', 'cost', 'mrp'],
        ['Running Shoe', '62910044202', '77', '150'],
    ]);

    expect((float) $this->blue->fresh()->cost)->toBe(77.0)
        ->and((float) $this->shoe->fresh()->cost)->toBe(85.0);
});

it('leaves the whole family alone when a name alone cannot say which variant', function (): void {
    importVariantRows($this->world, [
        ['name', 'cost', 'mrp'],
        ['Running Shoe', '11', '150'],
    ]);

    expect((float) $this->blue->fresh()->cost)->toBe((float) $this->shoe->cost)
        ->and((float) $this->shoe->fresh()->cost)->toBe(85.0)
        ->and(Product::where('name', 'Running Shoe')->count())->toBe(2);

    Notification::assertSentTo($this->world->user, ImportErrorsNotification::class);
});

it('makes the sale import pick a variant by size and colour, and refuse to guess', function (): void {
    $import = new SaleImport($this->world->user->id, 1, $this->world->branch->id);
    $byName = Closure::bind(fn (string $name, array $row) => $this->getProductIdByName($name, $row), $import, SaleImport::class);

    expect($byName('Running Shoe', ['size' => '42', 'color' => 'BLUE']))->toBe($this->blue->id)
        ->and($byName('Running Shoe', ['barcode' => '62910044101']))->toBe($this->shoe->id)
        ->and(fn () => $byName('Running Shoe', []))->toThrow(Exception::class, 'matches 2 variants');
});
