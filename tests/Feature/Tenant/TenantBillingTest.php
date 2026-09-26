<?php

use App\Livewire\Tenant\Page;
use App\Livewire\Tenant\Table;
use App\Livewire\Tenant\View;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Tenant Control billing: start / renewal dates, the AMC terms, the payments
 * a tenant has made, and when its users last signed in.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->other = Tenant::factory()->create(['name' => 'Acme Retail']);
});

it('saves the start date, renewal date and AMC terms from the edit modal', function (): void {
    Livewire::actingAs($this->world->user)->test(Page::class)
        ->call('edit', $this->other->id)
        ->set('tenants.started_on', '2026-01-10')
        ->set('tenants.renews_on', '2027-01-10')
        ->set('tenants.amc_amount', '1200')
        ->set('tenants.amc_cycle', 'yearly')
        ->call('save')
        ->assertHasNoErrors();

    $tenant = $this->other->fresh();
    expect($tenant->started_on->toDateString())->toBe('2026-01-10')
        ->and($tenant->renews_on->toDateString())->toBe('2027-01-10')
        ->and((float) $tenant->amc_amount)->toBe(1200.0)
        ->and($tenant->amcCycleLabel())->toBe('Yearly');
});

it('rejects a renewal date before the start date and accepts blank AMC fields', function (): void {
    Livewire::actingAs($this->world->user)->test(Page::class)
        ->call('edit', $this->other->id)
        ->set('tenants.started_on', '2026-05-01')
        ->set('tenants.renews_on', '2026-04-01')
        ->call('save')
        ->assertHasErrors(['tenants.renews_on' => 'after_or_equal']);

    Livewire::actingAs($this->world->user)->test(Page::class)
        ->call('edit', $this->other->id)
        ->set('tenants.started_on', '')
        ->set('tenants.renews_on', '')
        ->set('tenants.amc_amount', '')
        ->set('tenants.amc_cycle', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->other->fresh()->amc_amount)->toBeNull();
});

it('records an AMC payment that moves the renewal date one cycle forward', function (): void {
    $this->other->update(['renews_on' => '2026-10-01', 'amc_amount' => 900, 'amc_cycle' => 'quarterly']);

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->assertSet('payment.amount', '900.00')
        ->set('payment.paid_on', '2026-09-20')
        ->set('payment.reference', 'RCPT-1')
        ->call('savePayment')
        ->assertHasNoErrors();

    $payment = TenantPayment::where('tenant_id', $this->other->id)->sole();
    expect((float) $payment->amount)->toBe(900.0)
        ->and($payment->renewed_from->toDateString())->toBe('2026-10-01')
        ->and($payment->renewed_to->toDateString())->toBe('2027-01-01')
        ->and($payment->created_by)->toBe($this->world->user->id)
        ->and($this->other->fresh()->renews_on->toDateString())->toBe('2027-01-01');
});

it('leaves the renewal date alone for non-AMC payments or when unticked', function (): void {
    $this->other->update(['renews_on' => '2026-10-01']);

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->set('payment.type', 'setup')
        ->set('payment.amount', '500')
        ->call('savePayment')
        ->set('payment.amount', '300')
        ->set('extendRenewal', false)
        ->call('savePayment')
        ->assertHasNoErrors();

    expect(TenantPayment::where('tenant_id', $this->other->id)->count())->toBe(2)
        ->and(TenantPayment::whereNotNull('renewed_to')->exists())->toBeFalse()
        ->and($this->other->fresh()->renews_on->toDateString())->toBe('2026-10-01');
});

it('puts the renewal date back when the payment that moved it is deleted', function (): void {
    $this->other->update(['renews_on' => '2026-10-01', 'amc_cycle' => 'yearly']);

    $component = Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->set('payment.amount', '1000')
        ->call('savePayment');
    expect($this->other->fresh()->renews_on->toDateString())->toBe('2027-10-01');

    $component->call('deletePayment', TenantPayment::sole()->id);

    expect(TenantPayment::count())->toBe(0)
        ->and($this->other->fresh()->renews_on->toDateString())->toBe('2026-10-01');
});

it('lists payments on the Billing tab of their own tenant only', function (): void {
    TenantPayment::factory()->create(['tenant_id' => $this->other->id, 'amount' => 111, 'reference' => 'MINE-1']);
    TenantPayment::factory()->create(['tenant_id' => $this->world->tenant->id, 'amount' => 222, 'reference' => 'THEIRS-1']);

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->call('selectTab', 'billing')
        ->assertSee('MINE-1')
        ->assertDontSee('THEIRS-1')
        ->assertViewHas('paymentTotal', 111.0);
});

it('shows renewal and last login on the list and filters tenants due for renewal', function (): void {
    $this->other->update(['started_on' => '2025-09-01', 'renews_on' => today()->addDays(5)]);
    $far = Tenant::factory()->create(['name' => 'Far Away Ltd', 'renews_on' => today()->addYear()]);
    User::factory()->create(['tenant_id' => $this->other->id, 'last_login_at' => now()->subHours(3)]);

    Livewire::actingAs($this->world->user)->test(Table::class)
        ->assertSee('01 Sep 2025')
        ->assertSee('in 5 days')
        ->assertViewHas('data', fn ($data) => $data->firstWhere('id', $this->other->id)->users_max_last_login_at !== null)
        ->set('status', 'renewal_due')
        ->assertSee('Acme Retail')
        ->assertDontSee('Far Away Ltd')
        ->assertViewHas('counts', fn ($counts) => $counts['renewal_due'] === 1);

    expect($far->renewalState())->toBe('ok');
});

it('stamps last_login_at on sign-in but not while impersonating', function (): void {
    $user = User::factory()->create(['tenant_id' => $this->world->tenant->id]);

    event(new Login('web', $user, false));
    expect($user->fresh()->last_login_at)->not->toBeNull();

    $user->forceFill(['last_login_at' => null])->saveQuietly();
    session(['impersonator_id' => $this->world->user->id]);
    event(new Login('web', $user, false));
    expect($user->fresh()->last_login_at)->toBeNull();
});

it('stamps last_login_at when the mobile app signs in', function (): void {
    $this->world->user->forceFill(['password' => 'secret-pass', 'last_login_at' => null])->save();

    $this->postJson($this->world->url('/api/v1/login'), [
        'method' => 'password',
        'username' => $this->world->user->email,
        'password' => 'secret-pass',
    ])->assertOk();

    expect($this->world->user->fresh()->last_login_at)->not->toBeNull();
});
