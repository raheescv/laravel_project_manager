<?php

use App\Livewire\EmployeeInventory\Page;
use App\Models\Inventory;
use App\Models\User;
use App\Models\UserHasBranch;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The dedicated transfer page hands a basket of branch stock to one employee.
 * What matters: it only ever offers stock of the current branch, it never lets a
 * line exceed what the branch holds, and a successful transfer moves the quantity
 * off the branch row and onto an employee row.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 20);
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->employee = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'employee',
        'name' => 'Farooq Technician',
        'default_branch_id' => $this->world->branch->id,
        'is_active' => 1,
    ]);

    UserHasBranch::create([
        'user_id' => $this->employee->id,
        'branch_id' => $this->world->branch->id,
    ]);

    $permission = config('permission.models.permission');
    $this->world->user->givePermissionTo($permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'inventory.transfer',
        'guard_name' => 'web',
    ]));

    $this->inventory = Inventory::query()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->whereNull('employee_id')
        ->firstOrFail();
});

it('lists employees of the current branch and picks one', function (): void {
    Livewire::test(Page::class)
        ->set('employeeSearch', 'Farooq')
        ->assertSee('Farooq Technician')
        ->call('selectEmployee', $this->employee->id)
        ->assertSet('employee_id', $this->employee->id)
        ->assertSet('employee.name', 'Farooq Technician');
});

it('adds branch stock to the basket at quantity one', function (): void {
    Livewire::test(Page::class)
        ->call('addItem', $this->inventory->id)
        ->assertSet("items.{$this->inventory->id}.quantity", 1)
        ->assertSet("items.{$this->inventory->id}.available", 20.0);
});

it('never lets a line exceed what the branch holds', function (): void {
    Livewire::test(Page::class)
        ->call('addItem', $this->inventory->id)
        ->set("items.{$this->inventory->id}.quantity", 999)
        ->assertSet("items.{$this->inventory->id}.quantity", 20.0);
});

it('refuses to transfer without an employee, an item and a reason', function (): void {
    Livewire::test(Page::class)
        ->call('transfer')
        ->assertHasErrors(['employee_id', 'items', 'reason']);
});

it('moves the quantity from the branch row onto an employee row', function (): void {
    Livewire::test(Page::class)
        ->call('selectEmployee', $this->employee->id)
        ->call('addItem', $this->inventory->id)
        ->set("items.{$this->inventory->id}.quantity", 5)
        ->set('reason', 'Site installation at Villa 12')
        ->call('transfer')
        ->assertHasNoErrors()
        ->assertSet('items', []);

    expect(Inventory::find($this->inventory->id)->quantity)->toEqual(15);

    $employeeRow = Inventory::query()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->where('employee_id', $this->employee->id)
        ->first();

    expect($employeeRow)->not->toBeNull()
        ->and((float) $employeeRow->quantity)->toEqual(5.0);
});

it('shows what the employee already holds and returns it to the branch', function (): void {
    Livewire::test(Page::class)
        ->call('selectEmployee', $this->employee->id)
        ->call('addItem', $this->inventory->id)
        ->set("items.{$this->inventory->id}.quantity", 4)
        ->set('reason', 'Van stock top-up')
        ->call('transfer');

    $employeeRow = Inventory::query()
        ->where('employee_id', $this->employee->id)
        ->firstOrFail();

    Livewire::test(Page::class)
        ->call('selectEmployee', $this->employee->id)
        ->assertSee($this->world->product->name)
        ->call('returnToBranch', $employeeRow->id);

    expect((float) Inventory::find($employeeRow->id)->quantity)->toEqual(0.0)
        ->and((float) Inventory::find($this->inventory->id)->quantity)->toEqual(20.0);
});
