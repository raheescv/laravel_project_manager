<?php

use App\Livewire\Purchase\Page;
use App\Livewire\Purchase\View;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Purchase lines carry the product's size and colour ("41 / Black") so two
 * pairs of one model can be told apart in the cart and on the purchase view.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'purchase.create', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black']);
});

it('labels a purchase cart line with the size and colour', function (): void {
    $component = Livewire::test(Page::class)->set('product_id', $this->shoe->id);

    expect(collect($component->get('items'))->first()['variant'])->toBe('41 / Black');
});

it('leaves the label empty for a product without size or colour', function (): void {
    $this->shoe->update(['size' => null, 'color' => null]);

    $component = Livewire::test(Page::class)->set('product_id', $this->shoe->id);

    expect(collect($component->get('items'))->first()['variant'])->toBe('');
});

it('shows each item size and colour on the purchase view', function (): void {
    $purchaseId = DB::table('purchases')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'account_id' => $this->world->cashAccountId,
        'invoice_no' => 'PB-'.uniqid(),
        'date' => now()->toDateString(),
        'total' => 100,
        'paid' => 0,
        'status' => 'completed',
        'created_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('purchase_items')->insert([
        'purchase_id' => $purchaseId,
        'product_id' => $this->shoe->id,
        'unit_id' => $this->shoe->unit_id,
        'unit_price' => 50,
        'quantity' => 2,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(View::class, ['table_id' => $purchaseId])
        ->assertSeeHtml('<i class="fa fa-tag"></i> 41 / Black');

    expect(collect($component->get('items'))->first()['variant'])->toBe('41 / Black');
});
