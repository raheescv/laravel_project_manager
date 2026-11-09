<?php

use App\Models\Coupon;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\StorefrontCheckout;
use App\Models\Tenant;
use App\Services\TenantService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();

    foreach (config('permissions.coupon') as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "coupon.{$action}", 'guard_name' => 'web']));
    }

    $this->actingAs($this->world->user);
    $this->api = fn (string $path = ''): string => $this->world->url('/sale/coupon/api'.$path);
    $this->payload = fn (array $overrides = []): array => array_merge([
        'code' => 'save 20',
        'title' => '20% off',
        'discount_type' => 'percent',
        'discount_value' => 20,
        'applies_to' => 'all',
        'starts_on' => now()->toDateString(),
        'is_searchable' => true,
        'is_active' => true,
    ], $overrides);
    $this->markUsed = fn (Coupon $coupon) => StorefrontCheckout::withoutGlobalScope(AssignedBranchScope::class)->create([
        'reference' => bin2hex(random_bytes(16)),
        'branch_id' => $this->world->branch->id,
        'fulfilment' => 'pickup',
        'customer_name' => 'Customer',
        'customer_mobile' => '55123456',
        'customer_email' => 'c@example.com',
        'items' => [],
        'amount' => 100,
        'currency' => 'QAR',
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
        'coupon_discount' => 25,
        'gateway' => 'tap',
        'status' => StorefrontCheckout::STATUS_PAID,
    ]);
});

it('serves the console in the admin layout', function (): void {
    $this->withoutVite();

    $this->get($this->world->url('/sale/coupon'))
        ->assertOk()
        ->assertSee('id="coupon-console"', false);
});

it('creates a coupon, tidying the code', function (): void {
    $this->postJson(($this->api)(), ($this->payload)())->assertCreated();

    $coupon = Coupon::query()->sole();
    expect($coupon->code)->toBe('SAVE20')
        ->and($coupon->created_by)->toBe($this->world->user->id)
        ->and($coupon->applies_to_ids)->toBeNull();
});

it('updates a coupon and drops fields that no longer apply', function (): void {
    $coupon = Coupon::factory()->create(['code' => 'SAVE20', 'max_discount' => 100]);

    $this->putJson(($this->api)("/{$coupon->id}"), ($this->payload)([
        'code' => 'SAVE20',
        'discount_type' => 'fixed',
        'discount_value' => 150,
        'max_discount' => 100,
        'applies_to' => 'brand',
        'applies_to_ids' => [7, 9],
    ]))->assertOk();

    $coupon->refresh();
    expect((float) $coupon->discount_value)->toBe(150.0)
        ->and($coupon->max_discount)->toBeNull()
        ->and($coupon->applies_to_ids)->toBe([7, 9]);
});

it('keeps excepted products and names them in the list', function (): void {
    $this->postJson(($this->api)(), ($this->payload)(['excluded_product_ids' => [$this->world->product->id]]))->assertCreated();

    expect(Coupon::query()->sole()->excluded_product_ids)->toBe([$this->world->product->id]);

    $this->getJson(($this->api)())
        ->assertJsonPath('data.coupons.0.excluded_items.0.id', $this->world->product->id)
        ->assertJsonPath('data.coupons.0.excluded_items.0.name', $this->world->product->name);
});

it('drops excepted products when the coupon picks products one by one', function (): void {
    $this->postJson(($this->api)(), ($this->payload)([
        'applies_to' => 'product',
        'applies_to_ids' => [$this->world->product->id],
        'excluded_product_ids' => [99],
    ]))->assertCreated();

    expect(Coupon::query()->sole()->excluded_product_ids)->toBeNull();
});

it('explains what is wrong with each field', function (array $overrides, string $field, string $message): void {
    Coupon::factory()->create(['code' => 'TAKEN1']);

    $this->postJson(($this->api)(), ($this->payload)($overrides))
        ->assertUnprocessable()
        ->assertJsonPath("errors.{$field}.0", $message);
})->with([
    'duplicate code' => [['code' => 'taken1'], 'code', 'This code already exists.'],
    'short code' => [['code' => 'ab'], 'code', 'A code needs at least 3 characters.'],
    'odd characters' => [['code' => 'A_B%C'], 'code', 'Use only letters, numbers and dashes.'],
    'zero discount' => [['discount_value' => 0], 'discount_value', 'The discount must be above zero.'],
    'free order' => [['discount_value' => 100], 'discount_value', 'A percent discount must be below 100.'],
    'scoped with no picks' => [['applies_to' => 'brand'], 'applies_to_ids', 'Pick at least one.'],
    'ends before it starts' => [['starts_on' => '2026-12-10', 'ends_on' => '2026-12-01'], 'ends_on', 'It must end on or after the start date.'],
]);

it('allows a fixed discount above 100', function (): void {
    $this->postJson(($this->api)(), ($this->payload)(['discount_type' => 'fixed', 'discount_value' => 250]))->assertCreated();
});

it('allows the same code in another tenant', function (): void {
    $other = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($other);
    Coupon::factory()->create(['code' => 'SAVE20']);
    app(TenantService::class)->setCurrentTenant($this->world->tenant);

    $this->postJson(($this->api)(), ($this->payload)())->assertCreated();

    expect(Coupon::query()->pluck('tenant_id')->all())->toBe([$this->world->tenant->id]);
});

it('filters by state and keeps the summary whole', function (): void {
    Coupon::factory()->create(['code' => 'LIVE10']);
    Coupon::factory()->paused()->create(['code' => 'PAUSE10']);
    Coupon::factory()->expired()->create(['code' => 'OLD10']);
    Coupon::factory()->scheduled()->create(['code' => 'SOON10']);
    $usedUp = Coupon::factory()->create(['code' => 'GONE10', 'usage_limit' => 1]);
    ($this->markUsed)($usedUp);

    $response = $this->getJson(($this->api)().'?state=active')->assertOk();

    expect($response->json('data.coupons.*.code'))->toBe(['LIVE10'])
        ->and($response->json('data.summary'))->toMatchArray([
            'all' => 5,
            'states' => ['active' => 1, 'scheduled' => 1, 'paused' => 1, 'expired' => 1, 'used_up' => 1],
            'times_used' => 1,
            'discount_given' => 25,
        ]);

    expect($this->getJson(($this->api)().'?state=used_up')->json('data.coupons.0'))
        ->toMatchArray(['code' => 'GONE10', 'state' => 'used_up', 'used' => 1, 'discount_given' => 25]);
});

it('pauses and resumes a coupon', function (): void {
    $coupon = Coupon::factory()->create();

    $this->patchJson(($this->api)("/{$coupon->id}/toggle"), ['is_active' => false])->assertOk();
    expect($coupon->fresh()->is_active)->toBeFalse();

    $this->patchJson(($this->api)("/{$coupon->id}/toggle"), ['is_active' => true])->assertOk();
    expect($coupon->fresh()->is_active)->toBeTrue();
});

it('deletes an unused coupon but keeps one customers have paid with', function (): void {
    $unused = Coupon::factory()->create();
    $used = Coupon::factory()->create();
    ($this->markUsed)($used);

    $this->deleteJson(($this->api)("/{$unused->id}"))->assertOk();
    $this->deleteJson(($this->api)("/{$used->id}"))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Customers have used this coupon. Pause it instead.');

    expect(Coupon::query()->pluck('id')->all())->toBe([$used->id]);
});

it('blocks each action without its permission', function (string $action, string $method, string $path): void {
    $coupon = Coupon::factory()->create();
    $this->world->user->revokePermissionTo("coupon.{$action}");
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->json($method, ($this->api)(str_replace('{id}', (string) $coupon->id, $path)), ($this->payload)())->assertForbidden();
})->with([
    'view' => ['view', 'GET', ''],
    'create' => ['create', 'POST', ''],
    'edit' => ['edit', 'PUT', '/{id}'],
    'toggle' => ['edit', 'PATCH', '/{id}/toggle'],
    'delete' => ['delete', 'DELETE', '/{id}'],
]);
