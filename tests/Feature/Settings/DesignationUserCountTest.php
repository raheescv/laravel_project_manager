<?php

use App\Livewire\Settings\Designation\Table;
use App\Models\Designation;
use App\Models\User;
use App\Services\TenantService;
use Livewire\Livewire;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    app(TenantService::class)->setCurrentTenant($this->world->tenant);
    $this->actingAs($this->world->user);
});

it('shows how many users hold each designation', function (): void {
    $designation = Designation::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Counted '.uniqid()]);
    User::factory()->count(2)->create(['tenant_id' => $this->world->tenant->id, 'designation_id' => $designation->id]);

    $rows = Livewire::test(Table::class)->set('search', $designation->name)->viewData('data');

    expect($rows->firstWhere('id', $designation->id)->employees_count)->toBe(2);
});

it('sorts designations by user count', function (): void {
    Livewire::test(Table::class)->call('sortBy', 'employees_count')->assertOk();
});
