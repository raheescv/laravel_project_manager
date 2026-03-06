<?php

use App\Models\TailoringCategory;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    createTailoringPrintTestSchema();

    $this->withoutMiddleware();

    $this->tenant = Tenant::query()->create([
        'name' => 'Test Tenant',
        'code' => 'TT',
        'subdomain' => 'test-tenant',
    ]);

    app(TenantService::class)->setCurrentTenant($this->tenant);

    $this->user = User::query()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $this->unit = Unit::query()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Piece',
        'code' => 'PCS',
    ]);

    $this->category = TailoringCategory::query()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Kandoora',
        'is_active' => true,
    ]);
});

function createTailoringPrintTestSchema(): void
{
    collect([
        'tailoring_order_item_tailors',
        'tailoring_order_items',
        'tailoring_orders',
        'tailoring_category_measurements',
        'tailoring_category_model_types',
        'tailoring_category_models',
        'tailoring_categories',
        'products',
        'units',
        'jobs',
        'accounts',
        'branches',
        'users',
        'tenants',
    ])->each(fn (string $table) => Schema::dropIfExists($table));

    Schema::create('tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('subdomain')->unique();
        $table->string('domain')->nullable();
        $table->boolean('is_active')->default(true);
        $table->text('description')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('branches', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('accounts', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('account_type')->default('customer');
        $table->string('name');
        $table->string('mobile')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
        $table->string('code');
        $table->timestamps();
    });

    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
        $table->string('barcode')->nullable();
        $table->timestamps();
    });

    Schema::create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    Schema::create('tailoring_categories', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->integer('order')->default(0);
        $table->timestamps();
    });

    Schema::create('tailoring_category_models', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->unsignedBigInteger('tailoring_category_id');
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('tailoring_category_model_types', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->unsignedBigInteger('tailoring_category_id');
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('tailoring_category_measurements', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->unsignedBigInteger('tailoring_category_id');
        $table->string('field_key');
        $table->string('label');
        $table->string('section')->default('basic_body');
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('tailoring_orders', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('order_no');
        $table->unsignedBigInteger('branch_id')->nullable();
        $table->unsignedBigInteger('account_id')->nullable();
        $table->string('customer_name')->nullable();
        $table->string('customer_mobile')->nullable();
        $table->unsignedBigInteger('salesman_id')->nullable();
        $table->date('order_date');
        $table->date('delivery_date')->nullable();
        $table->decimal('gross_amount', 16, 2)->default(0);
        $table->decimal('item_discount', 16, 2)->default(0);
        $table->decimal('tax_amount', 16, 2)->default(0);
        $table->decimal('stitch_amount', 16, 2)->default(0);
        $table->decimal('total', 16, 2)->default(0);
        $table->decimal('other_discount', 16, 2)->default(0);
        $table->decimal('round_off', 10, 2)->default(0);
        $table->decimal('grand_total', 16, 2)->default(0);
        $table->decimal('paid', 16, 2)->default(0);
        $table->decimal('balance', 16, 2)->default(0);
        $table->string('status')->default('pending');
        $table->string('delivery_status')->default('not delivered');
        $table->unsignedBigInteger('cutter_id')->nullable();
        $table->unsignedTinyInteger('cutter_rating')->nullable();
        $table->date('completion_date')->nullable();
        $table->timestamp('cutting_slip_printed_at')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->unsignedBigInteger('deleted_by')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('tailoring_order_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->unsignedBigInteger('tailoring_order_id');
        $table->integer('item_no');
        $table->unsignedBigInteger('tailoring_category_id')->nullable();
        $table->unsignedBigInteger('tailoring_category_model_id')->nullable();
        $table->unsignedBigInteger('tailoring_category_model_type_id')->nullable();
        $table->unsignedBigInteger('product_id')->nullable();
        $table->string('product_name');
        $table->string('product_color')->nullable();
        $table->unsignedBigInteger('unit_id');
        $table->decimal('quantity', 8, 3)->default(1);
        $table->decimal('quantity_per_item', 8, 3)->default(1);
        $table->decimal('unit_price', 16, 2)->default(0);
        $table->decimal('stitch_rate', 16, 2)->default(0);
        $table->decimal('discount', 16, 2)->default(0);
        $table->decimal('tax', 16, 2)->default(0);
        $table->text('tailoring_notes')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->unsignedBigInteger('deleted_by')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('tailoring_order_item_tailors', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->unsignedBigInteger('tailoring_order_item_id');
        $table->unsignedBigInteger('tailor_id')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
}

function createTailoringOrderForCuttingSlip(Tenant $tenant, User $user, Unit $unit, TailoringCategory $category, array $attributes = []): TailoringOrder
{
    $sequence = TailoringOrder::query()->count() + 1;

    $order = TailoringOrder::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'order_no' => 'TO-'.$sequence,
        'order_date' => '2026-03-03',
        'customer_name' => 'Customer '.$sequence,
        'customer_mobile' => '90000000'.$sequence,
        'status' => 'pending',
        'created_by' => $user->id,
    ], $attributes));

    TailoringOrderItem::query()->create([
        'tenant_id' => $tenant->id,
        'tailoring_order_id' => $order->id,
        'item_no' => 1,
        'tailoring_category_id' => $category->id,
        'product_name' => 'Fabric '.$sequence,
        'unit_id' => $unit->id,
        'quantity' => 1,
        'quantity_per_item' => 1,
        'unit_price' => 10,
        'stitch_rate' => 2,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    return $order;
}

it('prints selected cutting slips and marks orders as printed', function (): void {
    $firstOrder = createTailoringOrderForCuttingSlip($this->tenant, $this->user, $this->unit, $this->category, [
        'order_no' => 'TO-1001',
        'customer_name' => 'First Customer',
    ]);
    $secondOrder = createTailoringOrderForCuttingSlip($this->tenant, $this->user, $this->unit, $this->category, [
        'order_no' => 'TO-1002',
        'customer_name' => 'Second Customer',
    ]);

    $response = $this->get(route('tailoring::order::print-cutting-slips', [
        'ids' => [$firstOrder->id, $secondOrder->id],
    ]));

    $response->assertSuccessful();
    $response->assertSee('TO-1001');
    $response->assertSee('TO-1002');

    expect($firstOrder->fresh()->cutting_slip_printed_at)->not->toBeNull();
    expect($secondOrder->fresh()->cutting_slip_printed_at)->not->toBeNull();
});

it('filters only unprinted cutting slips', function (): void {
    $printedOrder = createTailoringOrderForCuttingSlip($this->tenant, $this->user, $this->unit, $this->category, [
        'order_no' => 'TO-2001',
        'cutting_slip_printed_at' => Carbon::parse('2026-03-03 10:00:00'),
    ]);
    $unprintedOrder = createTailoringOrderForCuttingSlip($this->tenant, $this->user, $this->unit, $this->category, [
        'order_no' => 'TO-2002',
    ]);

    $filteredOrderIds = TailoringOrder::query()
        ->filter(['only_unprinted_cutting_slips' => true])
        ->pluck('id')
        ->all();

    expect($filteredOrderIds)->toContain($unprintedOrder->id);
    expect($filteredOrderIds)->not->toContain($printedOrder->id);
});
