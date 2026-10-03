<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Models\Tenant;
use App\Models\User;

/**
 * The rent-out data migration carries the old system's `nick_name` (its sign-in
 * name) across as the new `username`.
 */
function mapUsername(MigratePropertyDataCommand $command, object $row): array
{
    return (fn () => $this->withUsername($row, ['id' => $row->id]))->call($command);
}

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->command = new MigratePropertyDataCommand();
    (fn ($id) => $this->tenantId = $id)->call($this->command, $this->tenant->id);
    $this->command->setOutput(new Illuminate\Console\OutputStyle(
        new Symfony\Component\Console\Input\ArrayInput([]),
        new Symfony\Component\Console\Output\NullOutput(),
    ));
});

it('maps the nick name to a lower-cased username', function (): void {
    expect(mapUsername($this->command, (object) ['id' => 10, 'nick_name' => ' Wael Ali ']))
        ->toBe(['id' => 10, 'username' => 'wael.ali']);
});

it('leaves the username out when the nick name is blank or invalid', function (): void {
    expect(mapUsername($this->command, (object) ['id' => 11, 'nick_name' => null]))->toBe(['id' => 11])
        ->and(mapUsername($this->command, (object) ['id' => 12, 'nick_name' => 'Al$']))->toBe(['id' => 12]);
});

it('gives a duplicated nick name only to the first user', function (): void {
    expect(mapUsername($this->command, (object) ['id' => 20, 'nick_name' => 'Khaled']))->toHaveKey('username', 'khaled')
        ->and(mapUsername($this->command, (object) ['id' => 21, 'nick_name' => 'KHALED']))->toBe(['id' => 21])
        ->and(mapUsername($this->command, (object) ['id' => 20, 'nick_name' => 'Khaled']))->toHaveKey('username', 'khaled');
});

it('does not take a username another user already holds in the tenant', function (): void {
    User::factory()->create(['tenant_id' => $this->tenant->id, 'username' => 'rajesh']);

    expect(mapUsername($this->command, (object) ['id' => 999999, 'nick_name' => 'Rajesh']))->toBe(['id' => 999999]);
});
