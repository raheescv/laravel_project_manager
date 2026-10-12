<?php

use App\Http\Controllers\Property\PropertyLeadController;
use App\Livewire\Property\PropertyLead\Board;
use App\Livewire\Property\PropertyLead\Page;
use App\Livewire\Property\PropertyLead\Table;
use App\Livewire\User\EmployeeTeam;
use App\Models\EmployeeTeam as EmployeeTeamModel;
use App\Models\PropertyLead;
use App\Models\User;
use App\Support\LeadOptions;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\PosWorld;

/**
 * The old system's "Sales Team" rule: a salesman sees only the leads assigned
 * to them or to their team members; admins and 'property lead.view all' see all.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);

    $employee = fn (string $name) => User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'type' => 'employee',
        'is_admin' => 0,
        'name' => $name,
    ]);
    $this->salesman = $employee('Salesman Sam');
    $this->teammate = $employee('Teammate Tia');
    $this->outsider = $employee('Outsider Omar');

    $lead = fn (string $name, ?int $assignedTo) => PropertyLead::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'name' => $name,
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        'assigned_to' => $assignedTo,
    ]);
    $this->ownLead = $lead('Own Lead Alpha', $this->salesman->id);
    $this->teamLead = $lead('Team Lead Bravo', $this->teammate->id);
    $this->otherLead = $lead('Other Lead Charlie', $this->outsider->id);
    $this->unassignedLead = $lead('Unassigned Lead Delta', null);

    EmployeeTeamModel::create(['employee_id' => $this->salesman->id, 'team_member_id' => $this->teammate->id]);
});

function grantLeadPermission(User $user, string $action): void
{
    $permission = config('permission.models.permission');
    $user->givePermissionTo($permission::firstOrCreate(['name' => "property lead.{$action}", 'guard_name' => 'web']));
}

it('limits a salesman to their own and their team members\' leads', function (): void {
    $this->actingAs($this->salesman);

    expect(PropertyLead::query()->pluck('name')->all())
        ->toEqualCanonicalizing(['Own Lead Alpha', 'Team Lead Bravo']);

    Livewire::test(Table::class)
        ->set('fromDate', '')
        ->set('toDate', '')
        ->assertSee('Own Lead Alpha')
        ->assertSee('Team Lead Bravo')
        ->assertDontSee('Other Lead Charlie')
        ->assertDontSee('Unassigned Lead Delta');
});

it('keeps a salesman without a team to their own leads only', function (): void {
    $this->actingAs($this->teammate);

    expect(PropertyLead::query()->pluck('name')->all())->toBe(['Team Lead Bravo']);
});

it('leaves a back-office user seeing every lead', function (): void {
    $this->actingAs(User::factory()->create(['tenant_id' => $this->world->tenant->id, 'type' => 'user', 'is_admin' => 0]));

    expect(PropertyLead::query()->count())->toBe(4);
});

it('shows every lead to a salesman granted view all', function (): void {
    grantLeadPermission($this->salesman, 'view all');
    $this->actingAs($this->salesman);

    expect(PropertyLead::query()->count())->toBe(4);
});

it('shows every lead to an admin', function (): void {
    $this->actingAs($this->world->user);

    expect(PropertyLead::query()->count())->toBe(4);
});

it('keeps the board to the salesman\'s visible leads', function (): void {
    $this->actingAs($this->salesman);

    Livewire::test(Board::class)
        ->set('fromDate', '')
        ->set('toDate', '')
        ->assertSee('Own Lead Alpha')
        ->assertDontSee('Other Lead Charlie');
});

it('refuses to open another salesman\'s lead by URL', function (): void {
    grantLeadPermission($this->salesman, 'view');
    grantLeadPermission($this->salesman, 'edit');
    $this->actingAs($this->salesman);

    Livewire::test(Page::class, ['lead_id' => $this->teamLead->id])->assertOk();

    $controller = new PropertyLeadController();
    expect($controller->edit($this->teamLead->id)->getData()['lead_id'])->toBe($this->teamLead->id);
    expect(fn () => $controller->edit($this->otherLead->id))
        ->toThrow(fn (HttpException $e) => expect($e->getStatusCode())->toBe(404));
});

it('offers only the salesman and their team under Assigned To', function (): void {
    $this->actingAs($this->salesman);

    expect(array_keys(LeadOptions::assignees()))
        ->toEqualCanonicalizing([$this->salesman->id, $this->teammate->id]);
});

it('adds and removes team members from the employee view', function (): void {
    $permission = config('permission.models.permission');
    $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => 'employee.edit', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);

    $component = Livewire::test(EmployeeTeam::class, ['userId' => $this->salesman->id])
        ->assertSee('Teammate Tia')
        ->set('team_member_id', $this->outsider->id)
        ->call('add')
        ->assertDispatched('success');

    expect($this->salesman->teams()->pluck('team_member_id')->all())
        ->toEqualCanonicalizing([$this->teammate->id, $this->outsider->id]);

    $component->set('team_member_id', $this->outsider->id)->call('add')
        ->assertDispatched('error', ['message' => 'This employee is already in the team.']);

    $component->set('team_member_id', $this->salesman->id)->call('add')
        ->assertDispatched('error', ['message' => 'An employee cannot be added to their own team.']);

    $row = EmployeeTeamModel::where('team_member_id', $this->outsider->id)->first();
    $component->call('remove', $row->id)->assertDispatched('success');

    expect($this->salesman->teams()->pluck('team_member_id')->all())->toBe([$this->teammate->id]);
});

it('blocks managing a team without employee.edit', function (): void {
    $this->actingAs($this->salesman);

    Livewire::test(EmployeeTeam::class, ['userId' => $this->salesman->id])
        ->set('team_member_id', $this->outsider->id)
        ->call('add')
        ->assertForbidden();
});
