<?php

use App\Livewire\Sale\View as SaleView;
use App\Models\Sale;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The sale view's Audit report tab holds both reads of the same history —
 * the Timeline and the column-per-field Table — behind one switch.
 */
it('renders the timeline and table views inside the one audit report tab', function (): void {
    $world = PosWorld::create();
    Sanctum::actingAs($world->user);

    $saleId = $this->postJson($world->url('/api/v1/sale'), $world->salePayload())
        ->assertSuccessful()
        ->json('data.id');

    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);
    $world->user->givePermissionTo(Permission::findOrCreate('sale.audit view', 'web'));

    Livewire::test(SaleView::class, ['table_id' => Sale::withoutGlobalScopes()->findOrFail($saleId)->id])
        ->assertSee('data-bs-target="#tab-audit-report"', false)
        ->assertDontSee('Audit table')
        ->assertSeeInOrder(['Table', 'Timeline'])
        ->assertSee('x-show="auditView === \'timeline\'"', false)
        ->assertSee('x-show="auditView === \'table\'"', false)
        ->assertSee('class="adx"', false);
});
