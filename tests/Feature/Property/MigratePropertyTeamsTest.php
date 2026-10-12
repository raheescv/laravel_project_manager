<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Models\Tenant;
use App\Models\User;

/**
 * The rent-out data migration carries accounts' sales teams (employee_teams)
 * across onto the migrated employee users that leads are assigned to.
 */
function callMigrationMethod(MigratePropertyDataCommand $command, string $method, object $row): ?array
{
    return (fn () => $this->{$method}($row))->call($command);
}

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->command = new MigratePropertyDataCommand();
    (fn ($id) => $this->tenantId = $id)->call($this->command, $this->tenant->id);
    $this->command->setOutput(new Illuminate\Console\OutputStyle(
        new Symfony\Component\Console\Input\ArrayInput([]),
        new Symfony\Component\Console\Output\NullOutput(),
    ));

    $employee = fn (int $sourceId) => User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'type' => 'employee',
        'second_reference_no' => 'emp_'.$sourceId,
    ]);
    $this->lead = $employee(104);
    $this->member = $employee(102);
});

it('maps a team row from accounts employee ids to the migrated users', function (): void {
    expect(callMigrationMethod($this->command, 'employeeTeamRow', (object) ['employee_id' => 104, 'team_member_id' => 102, 'created_at' => '2025-04-27 11:50:28', 'updated_at' => '2025-04-27 11:50:28']))
        ->toMatchArray([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->lead->id,
            'team_member_id' => $this->member->id,
        ]);
});

it('skips a team row whose employee was not migrated', function (): void {
    expect(callMigrationMethod($this->command, 'employeeTeamRow', (object) ['employee_id' => 104, 'team_member_id' => 999]))->toBeNull();
});
