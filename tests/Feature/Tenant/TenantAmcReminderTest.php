<?php

use App\Livewire\Tenant\AmcReminder;
use App\Livewire\Tenant\View;
use App\Models\Tenant;
use App\Models\TenantPayment;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Tenant Control → AMC Reminders: tenants whose renewal is overdue or falls
 * inside the chosen window, soonest first.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();

    $this->overdue = Tenant::factory()->create(['name' => 'Overdue Mart', 'renews_on' => today()->subDays(3), 'amc_amount' => 500, 'amc_cycle' => 'yearly']);
    $this->thisWeek = Tenant::factory()->create(['name' => 'Weekly Traders', 'renews_on' => today()->addDays(5), 'amc_amount' => 300, 'amc_cycle' => 'monthly']);
    $this->later = Tenant::factory()->create(['name' => 'Later Stores', 'renews_on' => today()->addDays(50), 'amc_amount' => 1000]);
    $this->inactive = Tenant::factory()->inactive()->create(['name' => 'Dormant Shop', 'renews_on' => today()->addDays(2), 'amc_amount' => 200]);
    $this->unscheduled = Tenant::factory()->create(['name' => 'No Date Co']);
});

it('lets only super admins open the AMC reminders page', function (): void {
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $this->actingAs($this->world->user)->get($this->world->url('/tenants/amc-reminders'))->assertForbidden();

    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($this->world->user->fresh())->get($this->world->url('/tenants/amc-reminders'))
        ->assertOk()
        ->assertSee('AMC Reminders')
        ->assertSee('Overdue Mart');
});

it('lists overdue and upcoming renewals in the window, soonest first', function (): void {
    Livewire::actingAs($this->world->user)->test(AmcReminder::class)
        ->assertSeeInOrder(['Overdue Mart', 'Weekly Traders'])
        ->assertDontSee('Later Stores')
        ->assertDontSee('Dormant Shop')
        ->assertDontSee('No Date Co')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['overdue'] === [1, 500.0]
            && $summary['week'] === [1, 300.0]
            && $summary['window'] === [2, 800.0])
        ->set('window', '60')
        ->assertSee('Later Stores')
        ->set('window', 'overdue')
        ->assertSee('Overdue Mart')
        ->assertDontSee('Weekly Traders');
});

it('includes inactive tenants on request and filters by search', function (): void {
    Livewire::actingAs($this->world->user)->test(AmcReminder::class)
        ->set('includeInactive', true)
        ->assertSee('Dormant Shop')
        ->set('search', 'Weekly')
        ->assertSee('Weekly Traders')
        ->assertDontSee('Overdue Mart');
});

it('shows the last payment date and counts AMC collected this month', function (): void {
    TenantPayment::factory()->create(['tenant_id' => $this->overdue->id, 'paid_on' => '2025-06-15']);
    TenantPayment::factory()->create(['tenant_id' => $this->thisWeek->id, 'paid_on' => today(), 'type' => 'amc', 'amount' => 150]);
    TenantPayment::factory()->create(['tenant_id' => $this->thisWeek->id, 'paid_on' => today(), 'type' => 'setup', 'amount' => 999]);

    Livewire::actingAs($this->world->user)->test(AmcReminder::class)
        ->assertSee('15 Jun 2025')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['collected'] === 150.0
            && $summary['scheduled'] === 3
            && $summary['unscheduled'] >= 1);
});

it('sorts by AMC amount and ignores unknown sort fields', function (): void {
    Livewire::actingAs($this->world->user)->test(AmcReminder::class)
        ->call('sortBy', 'amc_amount')
        ->assertSeeInOrder(['Weekly Traders', 'Overdue Mart'])
        ->call('sortBy', 'amc_amount')
        ->assertSeeInOrder(['Overdue Mart', 'Weekly Traders'])
        ->call('sortBy', 'deleted_at')
        ->assertSet('sortField', 'amc_amount')
        ->call('resetFilters')
        ->assertSet('sortField', 'renews_on');
});

it('opens the tenant view on the tab given in the query string', function (): void {
    Livewire::actingAs($this->world->user)->withQueryParams(['tab' => 'billing'])
        ->test(View::class, ['tenantId' => $this->overdue->id])
        ->assertSet('selected_tab', 'billing');
});
