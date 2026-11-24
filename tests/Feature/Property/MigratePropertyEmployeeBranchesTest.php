<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

/**
 * accounts kept an employee's branch on the employee row, so the migration attaches
 * each migrated employee to that branch; otherwise the salesman picker lists nobody.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($this->tenant);
    $this->branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main Branch', 'code' => 'MB']);
    $this->command = new MigratePropertyDataCommand();
    (function (int $tenantId): void {
        $this->tenantId = $tenantId;
        $this->dryRun = false;
    })->call($this->command, $this->tenant->id);
    $this->command->setOutput(new Illuminate\Console\OutputStyle(
        new Symfony\Component\Console\Input\ArrayInput([]),
        new Symfony\Component\Console\Output\NullOutput(),
    ));
});

it('attaches each migrated employee to its default branch', function (): void {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'employee', 'default_branch_id' => $this->branch->id]);

    (fn () => $this->migrateEmployeeBranches())->call($this->command);
    (fn () => $this->migrateEmployeeBranches())->call($this->command);

    expect(DB::table('user_has_branches')->where('user_id', $employee->id)->pluck('branch_id')->all())->toBe([$this->branch->id]);
});

it('skips employees whose default branch is not in the tenant', function (): void {
    $otherBranch = Branch::withoutGlobalScopes()->create(['tenant_id' => Tenant::factory()->create()->id, 'name' => 'Other Branch', 'code' => 'OB']);
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'employee', 'default_branch_id' => $otherBranch->id]);

    (fn () => $this->migrateEmployeeBranches())->call($this->command);

    expect(DB::table('user_has_branches')->where('user_id', $employee->id)->exists())->toBeFalse();
});

it('lists the migrated employee in the employee dropdown for its branch', function (): void {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'employee', 'default_branch_id' => $this->branch->id, 'is_active' => true]);

    (fn () => $this->migrateEmployeeBranches())->call($this->command);
    session(['branch_id' => $this->branch->id]);

    expect(collect((new User())->getDropDownList(['type' => 'employee'])['items'])->pluck('id'))->toContain($employee->id);
});
