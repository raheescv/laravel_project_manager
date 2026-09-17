<?php

use App\Livewire\Report\Sale\CategoryWiseReport;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /report/sale_category — completed sale lines less completed return lines,
 * rolled up to each product's main category, with a drill-down into the
 * category's products. These pin the netting, the drill-down, the filters and
 * the export's permission guard.
 *
 * @see App\Livewire\Report\Sale\CategoryWiseReport
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $tenant = $this->world->tenant->id;
    $user = $this->world->user;

    $this->actingAs($user);
    session(['branch_id' => $this->world->branch->id]);
    DB::table('user_has_branches')->insert(['user_id' => $user->id, 'branch_id' => $this->world->branch->id, 'created_at' => now(), 'updated_at' => now()]);

    $user->givePermissionTo(Permission::firstOrCreate(['tenant_id' => $tenant, 'name' => 'report.sale category', 'guard_name' => 'web']));

    $this->shoesId = DB::table('categories')->insertGetId(['tenant_id' => $tenant, 'name' => 'Shoes']);

    $this->product = function (string $name, float $price, ?int $categoryId, string $type = 'product') use ($tenant, $user): Product {
        return Product::create([
            'tenant_id' => $tenant,
            'type' => $type,
            'name' => $name,
            'code' => 'P'.Str::upper(Str::random(6)),
            'unit_id' => $this->world->product->unit_id,
            'department_id' => $this->world->product->department_id,
            'main_category_id' => $categoryId,
            'mrp' => $price,
            'tax' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    };

    // [product, quantity, discount] lines on one completed bill dated today (or $date).
    $this->sell = function (array $lines, ?string $date = null, ?int $employeeId = null) use ($tenant, $user): int {
        $saleId = DB::table('sales')->insertGetId([
            'tenant_id' => $tenant,
            'invoice_no' => 'INV'.Str::upper(Str::random(6)),
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['general_customer'],
            'date' => $date ?? now()->toDateString(),
            'status' => 'completed',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ($lines as [$product, $quantity, $discount]) {
            DB::table('sale_items')->insert([
                'tenant_id' => $tenant,
                'sale_id' => $saleId,
                'employee_id' => $employeeId ?? $user->id,
                'inventory_id' => 0,
                'product_id' => $product->id,
                'unit_id' => $product->unit_id,
                'unit_price' => $product->mrp,
                'quantity' => $quantity,
                'discount' => $discount,
                'tax' => 0,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $saleId;
    };

    $this->giveBack = function (Product $product, float $quantity) use ($tenant, $user): void {
        $returnId = DB::table('sale_returns')->insertGetId([
            'tenant_id' => $tenant,
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['general_customer'],
            'date' => now()->toDateString(),
            'status' => 'completed',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sale_return_items')->insert([
            'tenant_id' => $tenant,
            'sale_return_id' => $returnId,
            'inventory_id' => 0,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'employee_id' => $user->id,
            'unit_price' => $product->mrp,
            'quantity' => $quantity,
            'discount' => 0,
            'tax' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $this->sneaker = ($this->product)('Sneaker', 30, $this->shoesId);
    $this->sandal = ($this->product)('Sandal', 10, $this->shoesId);

    // General: 2 × 50 = 100. Shoes: (30 − 5) + 3 × 10 = 55, then one sandal comes back → 45.
    ($this->sell)([[$this->world->product, 2, 0], [$this->sneaker, 1, 5]]);
    ($this->sell)([[$this->sandal, 3, 0]]);
    ($this->giveBack)($this->sandal, 1);
});

it('nets each main category and ranks by net sales', function (): void {
    $component = Livewire::test(CategoryWiseReport::class)
        ->assertOk()
        ->assertSet('preset', 'today')
        ->assertSee('General')
        ->assertSee('Shoes');

    $rows = collect($component->viewData('data')->items());
    $shoes = $rows->firstWhere('group_name', 'Shoes');

    expect($rows->pluck('group_name')->all())->toBe(['General', 'Shoes'])
        ->and((float) $shoes->gross_amount)->toEqual(60.0)
        ->and((float) $shoes->discount)->toEqual(5.0)
        ->and((float) $shoes->return_total)->toEqual(10.0)
        ->and((float) $shoes->net_total)->toEqual(45.0)
        ->and((float) $shoes->quantity)->toEqual(3.0)
        ->and((int) $shoes->products_count)->toBe(2)
        ->and((int) $shoes->bills_count)->toBe(2)
        ->and($component->viewData('summary'))->toMatchArray([
            'categories' => 2,
            'bills_count' => 2,
            'returns_count' => 1,
            'gross_amount' => 160.0,
            'discount' => 5.0,
            'return_total' => 10.0,
            'net_total' => 145.0,
        ]);
});

it('opens a category into its products and closes it again', function (): void {
    $component = Livewire::test(CategoryWiseReport::class)
        ->call('toggle', (string) $this->shoesId)
        ->assertSet('expanded', (string) $this->shoesId)
        ->assertSee('Top products in Shoes')
        ->assertSeeInOrder(['Sneaker', 'Sandal']);

    expect($component->viewData('products')->pluck('net_total')->map(fn ($v) => (float) $v)->all())->toBe([25.0, 20.0]);

    $component->call('toggle', (string) $this->shoesId)
        ->assertSet('expanded', null)
        ->assertDontSee('Top products in Shoes');
});

it('keeps sales whose category was deleted, as Uncategorised', function (): void {
    DB::table('categories')->where('id', $this->shoesId)->delete();

    $component = Livewire::test(CategoryWiseReport::class)
        ->assertSee('Uncategorised')
        ->call('toggle', CategoryWiseReport::UNCATEGORISED)
        ->assertSee('Top products in Uncategorised');

    $orphan = collect($component->viewData('data')->items())->firstWhere('group_id', null);

    expect((float) $orphan->net_total)->toEqual(45.0)
        ->and($component->viewData('products'))->toHaveCount(2)
        ->and($component->viewData('summary')['net_total'])->toEqual(145.0);
});

it('follows the period, type, branch and staff filters', function (): void {
    ($this->sell)([[$this->world->product, 1, 0]], now()->subDays(3)->toDateString());
    $haircut = ($this->product)('Haircut', 40, $this->shoesId, 'service');
    $stylist = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'default_branch_id' => $this->world->branch->id, 'type' => 'employee']);
    ($this->sell)([[$haircut, 1, 0]], null, $stylist->id);

    $net = fn ($component) => $component->viewData('summary')['net_total'];

    $component = Livewire::test(CategoryWiseReport::class);
    expect($net($component))->toEqual(185.0);

    $component->call('setRange', '7d');
    expect($net($component))->toEqual(235.0);

    $component->set('from_date', now()->subDays(3)->toDateString())->set('to_date', now()->subDays(3)->toDateString())->assertSet('preset', 'custom');
    expect($net($component))->toEqual(50.0);

    $component->call('setRange', 'today')->call('setProductType', 'service');
    expect($net($component))->toEqual(40.0);

    $component->call('setProductType', 'nonsense')->assertSet('product_type', '')->set('employee_id', $stylist->id);
    expect($net($component))->toEqual(40.0);

    $component->set('employee_id', '')->set('branch_id', $this->world->addBranch()->id);
    expect($net($component))->toEqual(0.0);
});

it('searches category names without moving the period totals', function (): void {
    $component = Livewire::test(CategoryWiseReport::class)->set('search', 'sho');

    expect(collect($component->viewData('data')->items())->pluck('group_name')->all())->toBe(['Shoes'])
        ->and($component->viewData('summary')['net_total'])->toEqual(145.0);
});

it('sorts only on whitelisted columns', function (): void {
    $component = Livewire::test(CategoryWiseReport::class)
        ->call('sortBy', 'return_total')
        ->assertSet('sortField', 'return_total')
        ->call('sortBy', 'sales.id; drop table sales')
        ->assertSet('sortField', 'return_total');

    expect(collect($component->viewData('data')->items())->pluck('group_name')->first())->toBe('Shoes');
});

it('exports for permitted users only', function (): void {
    Livewire::test(CategoryWiseReport::class)
        ->call('export')
        ->assertFileDownloaded();

    $this->world->user->revokePermissionTo('report.sale category');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire::test(CategoryWiseReport::class)
        ->call('export')
        ->assertForbidden();
});
