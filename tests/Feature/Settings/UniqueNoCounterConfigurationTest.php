<?php

use App\Livewire\Settings\UniqueNoCounterConfiguration;
use App\Models\UniqueNoCounter;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    grantCounterPermissions($this->world->user, ['sale.create', 'grn.create', 'tailoring order.create', 'product.create']);
    $this->actingAs($this->world->user->fresh());
});

/** @param  list<string>  $permissions */
function grantCounterPermissions(App\Models\User $user, array $permissions): void
{
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
}

it('groups counters by document and saves an edited number', function (): void {
    UniqueNoCounter::query()->create(['year' => '26', 'branch_code' => 'ZZ1', 'segment' => 'Sale', 'number' => 41]);

    $component = Livewire::test(UniqueNoCounterConfiguration::class)
        ->assertSee('Document Numbering')
        ->assertSee('ZZ1')
        ->assertSee('42');

    $index = collect($component->get('rows'))->search(fn (array $row): bool => $row['branch_code'] === 'ZZ1');

    $component->set("rows.{$index}.number", 100)->call('save')->assertHasNoErrors();

    expect(UniqueNoCounter::query()->where('branch_code', 'ZZ1')->value('number'))->toBe(100);
});

it('offers every unused segment per branch as a new row and creates it on save', function (): void {
    $highestBarcode = (int) DB::table('inventories')->where('tenant_id', $this->world->tenant->id)->max(DB::raw('CAST(barcode_number AS UNSIGNED)'));

    $component = Livewire::test(UniqueNoCounterConfiguration::class)
        ->assertSee('GRN')
        ->assertSee('Tailoring Order')
        ->assertSee('Barcode');

    $rows = collect($component->get('rows'))->where('branch_code', 'MB')->keyBy('segment');

    expect($rows->keys()->all())->toContain('Sale', 'Grn', 'TailoringOrder', 'Barcode')
        ->and($rows['Grn'])->toMatchArray(['year' => now()->format('y'), 'number' => 0, 'is_new' => true])
        ->and($rows['Barcode'])->toMatchArray(['year' => '1', 'is_new' => true])
        ->and($rows['Barcode']['number'])->toBeGreaterThanOrEqual($highestBarcode);

    $component->call('save')->assertHasNoErrors();

    expect(UniqueNoCounter::query()->where('branch_code', 'MB')->pluck('segment')->sort()->values()->all())
        ->toBe(['Barcode', 'Grn', 'Sale', 'TailoringOrder']);
});

it('seeds a missing sale counter from the branch\'s highest invoice number', function (): void {
    Sanctum::actingAs($this->world->user);
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())->assertSuccessful();

    $issued = UniqueNoCounter::query()->where('segment', 'Sale')->where('branch_code', 'MB')->value('number');
    UniqueNoCounter::query()->where('segment', 'Sale')->where('branch_code', 'MB')->delete();

    $this->actingAs($this->world->user->fresh());
    $row = collect(Livewire::test(UniqueNoCounterConfiguration::class)->get('rows'))
        ->first(fn (array $row): bool => $row['segment'] === 'Sale' && $row['branch_code'] === 'MB');

    expect($issued)->toBeGreaterThan(0)
        ->and($row)->toMatchArray(['number' => $issued, 'is_new' => true]);
});

it('shows and saves only the segments the user has permission for', function (): void {
    $user = $this->world->user;
    $user->revokePermissionTo(['grn.create', 'tailoring order.create', 'product.create']);
    UniqueNoCounter::query()->create(['year' => '26', 'branch_code' => 'MB', 'segment' => 'Grn', 'number' => 9]);
    $this->actingAs($user->fresh());

    $component = Livewire::test(UniqueNoCounterConfiguration::class)
        ->assertDontSee('Tailoring Order')
        ->assertDontSee('fa-barcode');

    expect(collect($component->get('rows'))->pluck('segment')->unique()->values()->all())->toBe(['Sale']);

    $component->set('rows', [['year' => '26', 'branch_code' => 'MB', 'segment' => 'Grn', 'number' => 500, 'is_new' => false]])
        ->call('save')
        ->assertHasNoErrors();

    expect(UniqueNoCounter::query()->where('segment', 'Grn')->value('number'))->toBe(9);
});

/**
 * Give the world's product and its inventory row known barcodes, plus a second
 * product-wise pair sharing one barcode; the world's product keeps a manufacturer EAN above the counter.
 */
function seedBarcodeSeries(Tests\Support\PosWorld $world): void
{
    UniqueNoCounter::query()->where('segment', 'Barcode')->delete();
    UniqueNoCounter::query()->create(['year' => '1', 'branch_code' => 'MB', 'segment' => 'Barcode', 'number' => 1050]);
    UniqueNoCounter::query()->create(['year' => '1', 'branch_code' => 'XB', 'segment' => 'Barcode', 'number' => 1040]);

    DB::table('products')->where('tenant_id', $world->tenant->id)->update(['barcode_number' => '6291041500213']);
    DB::table('product_units')->where('tenant_id', $world->tenant->id)->delete();
    DB::table('inventories')->where('tenant_id', $world->tenant->id)->update(['barcode_number' => '1050']);

    $second = $world->product->replicate(['barcode']);
    $second->forceFill(['name' => 'Second', 'code' => 'SECOND-'.Str::random(6), 'barcode_number' => '1007'])->saveQuietly();
    DB::table('inventories')->insert(collect((array) DB::table('inventories')->where('tenant_id', $world->tenant->id)->first())
        ->except(['id', 'barcode', 'total'])
        ->merge(['product_id' => $second->id, 'barcode_number' => '1007', 'batch' => 'second'])
        ->all());
}

it('renumbers the barcode series in order and keeps shared barcodes matched', function (): void {
    seedBarcodeSeries($this->world);
    $secondId = DB::table('products')->where('tenant_id', $this->world->tenant->id)->where('barcode_number', '1007')->value('id');

    Livewire::test(UniqueNoCounterConfiguration::class)
        ->call('openBarcodeRenumber')
        ->assertSet('barcodeSeries', ['count' => 2, 'first' => 1007, 'last' => 1050])
        ->assertSet('barcodeStart', 1007)
        ->assertSee('Start from')
        ->set('barcodeStart', 1)
        ->call('renumberBarcodes')
        ->assertHasNoErrors()
        ->assertSet('barcodeSeries', null)
        ->assertDispatched('success');

    expect(DB::table('products')->where('id', $secondId)->value('barcode_number'))->toBe('1')
        ->and(DB::table('inventories')->where('product_id', $secondId)->value('barcode_number'))->toBe('1')
        ->and(DB::table('inventories')->where('product_id', $this->world->product->id)->value('barcode_number'))->toBe('2')
        ->and(DB::table('products')->where('id', $this->world->product->id)->value('barcode_number'))->toBe('6291041500213')
        ->and(DB::table('inventories')->where('product_id', $secondId)->value('barcode'))->toBe('1')
        ->and(UniqueNoCounter::query()->where('segment', 'Barcode')->pluck('number')->unique()->values()->all())->toBe([2]);

    $productAudit = App\Models\Product::query()->find($secondId)->audits()->where('tags', 'barcode-renumber')->sole();
    $inventoryAudits = OwenIt\Auditing\Models\Audit::query()->where('tags', 'barcode-renumber')->where('auditable_type', App\Models\Inventory::class)->get();

    expect($productAudit->event)->toBe('updated')
        ->and($productAudit->old_values)->toBe(['barcode_number' => '1007'])
        ->and($productAudit->new_values)->toBe(['barcode_number' => '1'])
        ->and($productAudit->user_id)->toBe($this->world->user->id)
        ->and($inventoryAudits)->toHaveCount(2)
        ->and($inventoryAudits->pluck('new_values')->sortBy('barcode_number')->values()->all())->toBe([['barcode_number' => '1'], ['barcode_number' => '2']])
        ->and(App\Models\Product::query()->find($this->world->product->id)->audits()->where('tags', 'barcode-renumber')->exists())->toBeFalse();
});

it('refuses a start number that would run into a barcode outside the series', function (): void {
    seedBarcodeSeries($this->world);
    DB::table('products')->where('id', $this->world->product->id)->update(['barcode_number' => '2001']);

    Livewire::test(UniqueNoCounterConfiguration::class)
        ->call('openBarcodeRenumber')
        ->set('barcodeStart', 2000)
        ->call('renumberBarcodes')
        ->assertDispatched('error');

    expect(DB::table('inventories')->where('product_id', $this->world->product->id)->value('barcode_number'))->toBe('1050')
        ->and(UniqueNoCounter::query()->where('segment', 'Barcode')->max('number'))->toBe(1050);
});

it('validates the start number', function (): void {
    seedBarcodeSeries($this->world);

    Livewire::test(UniqueNoCounterConfiguration::class)
        ->call('openBarcodeRenumber')
        ->set('barcodeStart', 0)
        ->call('renumberBarcodes')
        ->assertHasErrors(['barcodeStart' => 'min']);
});

it('hides barcode renumbering from users who cannot create products', function (): void {
    $this->world->user->revokePermissionTo('product.create');
    $this->actingAs($this->world->user->fresh());

    Livewire::test(UniqueNoCounterConfiguration::class)
        ->assertDontSee('Renumber')
        ->call('openBarcodeRenumber')
        ->assertForbidden();
});
