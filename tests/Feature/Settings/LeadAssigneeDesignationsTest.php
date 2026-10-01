<?php

use App\Livewire\Property\PropertyLead\Board;
use App\Livewire\Property\PropertyLead\Calendar;
use App\Livewire\Property\PropertyLead\Page;
use App\Livewire\Property\PropertyLead\Table;
use App\Livewire\Settings\LeadAssigneeDesignations;
use App\Models\Designation;
use App\Models\User;
use App\Support\LeadOptions;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Lead Settings picks the designations a lead can be assigned to;
 * the lead form's employee picker then lists only those employees.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);

    $permission = config('permission.models.permission');
    $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));

    $this->salesman = Designation::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Salesman']);
    $this->technician = Designation::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Technician']);
});

function leadAssigneeEmployee(Designation $designation, string $name): User
{
    $employee = User::factory()->create([
        'tenant_id' => test()->world->tenant->id,
        'type' => 'employee',
        'name' => $name,
        'designation_id' => $designation->id,
        'is_active' => 1,
    ]);
    $employee->branches()->create(['branch_id' => test()->world->branch->id]);

    return $employee;
}

it('toggles a designation on and off with a tap', function (): void {
    Livewire::test(LeadAssigneeDesignations::class)
        ->call('toggle', $this->salesman->id)
        ->assertSee('lists only employees with a selected designation');
    expect(LeadOptions::assigneeDesignationIds())->toBe([$this->salesman->id]);

    Livewire::test(LeadAssigneeDesignations::class)->call('toggle', $this->salesman->id);
    expect(LeadOptions::assigneeDesignationIds())->toBe([]);
});

it('lists only employees of the chosen designations in the assignee picker', function (): void {
    leadAssigneeEmployee($this->salesman, 'Sam Seller');
    leadAssigneeEmployee($this->technician, 'Tom Fixer');

    $names = fn (string $query) => collect($this->withSession(['branch_id' => $this->world->branch->id])->getJson($this->world->url('/users/list?type=employee'.$query))->json('items'))->pluck('name');

    expect($names(''))->toContain('Sam Seller', 'Tom Fixer');
    expect($names('&designation_ids='.$this->salesman->id))->toContain('Sam Seller')->not->toContain('Tom Fixer');
});

it('passes the chosen designations to the lead form picker', function (): void {
    LeadOptions::save(LeadOptions::ASSIGNEE_DESIGNATIONS, [$this->salesman->id, $this->technician->id]);

    Livewire::test(Page::class)
        ->assertSeeHtml('data-designations="'.$this->salesman->id.','.$this->technician->id.'"');
});

it('is closed to users without the settings permission', function (): void {
    $this->actingAs(User::factory()->create(['tenant_id' => $this->world->tenant->id]));

    Livewire::test(LeadAssigneeDesignations::class)->assertForbidden();
});

it('limits the salesman filter on the lead list, board and calendar too', function (): void {
    leadAssigneeEmployee($this->salesman, 'Sam Seller');
    leadAssigneeEmployee($this->technician, 'Tom Fixer');

    expect(LeadOptions::assignees())->toContain('Sam Seller', 'Tom Fixer');

    LeadOptions::save(LeadOptions::ASSIGNEE_DESIGNATIONS, [$this->salesman->id]);

    expect(LeadOptions::assignees())->toContain('Sam Seller')->not->toContain('Tom Fixer');
    Livewire::test(Table::class)->assertSee('Sam Seller')->assertDontSee('Tom Fixer');
    Livewire::test(Calendar::class)->assertSee('Sam Seller')->assertDontSee('Tom Fixer');
    Livewire::test(Board::class)->assertSeeHtml('designation_ids='.$this->salesman->id);
});
