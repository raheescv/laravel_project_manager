<?php

use App\Livewire\Product\Audit;
use App\Livewire\Product\Page;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::findOrCreate('product.audit view', 'web'));
    $this->actingAs($this->world->user->fresh());
    session(['branch_id' => $this->world->branch->id]);
});

it('shows the product audit with one column per changed field', function (): void {
    $this->world->product->audits()->create([
        'event' => 'updated',
        'user_type' => User::class,
        'user_id' => $this->world->user->id,
        'old_values' => ['name' => 'Old Shirt', 'mrp' => 40],
        'new_values' => ['name' => 'New Shirt', 'mrp' => 55],
    ]);

    Livewire::withoutLazyLoading()
        ->test(Audit::class, ['product_id' => $this->world->product->id])
        ->assertSee('Old Shirt')
        ->assertSee('New Shirt')
        ->assertSeeHtml('class="adx"')
        ->assertSeeHtml('.adx-user {')
        ->assertSee($this->world->user->name);
});

it('adds the Audit tab to the edit page only for users who may view audits', function (): void {
    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->assertSeeHtml('data-bs-target="#tabAudit"');

    $this->world->user->revokePermissionTo('product.audit view');
    $this->actingAs($this->world->user->fresh());

    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->assertDontSeeHtml('data-bs-target="#tabAudit"');

    Livewire::withoutLazyLoading()
        ->test(Audit::class, ['product_id' => $this->world->product->id])
        ->assertForbidden();
});
