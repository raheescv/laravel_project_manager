<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Models\Tenant;
use App\Models\User;

/**
 * The rent-out data migration carries accounts' branches and utility service
 * requests across, keeping ids and resolving users the way the other rows do.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->command = new MigratePropertyDataCommand();
    (fn ($id) => $this->tenantId = $id)->call($this->command, $this->tenant->id);

    $this->requester = User::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'user', 'second_reference_no' => 13]);
    $this->call = fn (string $method, mixed ...$args) => (fn () => $this->{$method}(...$args))->call($this->command);
});

it('maps a service request with its location, occupying rent-out, users and notes', function (): void {
    $row = (object) [
        'id' => 77,
        'property_id' => 5,
        'utility_id' => 1,
        'type' => 'connect',
        'date' => '2024-08-13',
        'amount' => '300.00',
        'rentout_service_status' => 'Excluded',
        'status' => 'financial approved',
        'requested_by' => 13,
        'requested_at' => '2024-10-07',
        'approved_by' => 13,
        'approved_at' => '2024-10-07',
        'financial_approved_by' => 999,
        'financial_approved_at' => '2024-10-08',
        'completed_by' => null,
        'completed_at' => null,
        'remarks' => 'tenant call',
        'notes' => json_encode([['note' => 'As per owner request', 'creator' => 'Sobeh', 'created_at' => '2024-10-07 13:48:59', 'created_by' => 13], ['note' => '']]),
        'created_at' => '2024-10-07 13:48:59',
        'updated_at' => '2024-10-08 09:00:00',
    ];
    $meta = (object) ['property_group_id' => 2, 'property_building_id' => 3, 'property_type_id' => 4];
    $rentOuts = collect([
        (object) ['id' => 40, 'start_date' => '2025-01-01', 'end_date' => '2025-12-31'],
        (object) ['id' => 30, 'start_date' => '2024-01-01', 'end_date' => '2024-12-31'],
    ]);

    $mapped = ($this->call)('serviceRequestRow', $row, $meta, 2, $rentOuts);

    expect($mapped)->toMatchArray([
        'id' => 77,
        'tenant_id' => $this->tenant->id,
        'branch_id' => 2,
        'property_building_id' => 3,
        'rent_out_id' => 30,
        'status' => 'financial_approved',
        'rent_out_service_status' => 'Excluded',
        'requested_by' => $this->requester->id,
        'approved_by' => $this->requester->id,
        'financial_approved_by' => null,
        'amount' => 300.0,
    ]);
    expect(json_decode($mapped['notes'], true))->toBe([
        ['note' => 'As per owner request', 'created_by' => $this->requester->id, 'creator' => 'Sobeh', 'created_at' => '2024-10-07 13:48:59'],
    ]);
});

it('keeps a branch id and takes its contact from the company profile', function (): void {
    $branch = (object) ['id' => 4, 'name' => ' Dubai ', 'code' => 'D', 'created_at' => null, 'updated_at' => null];
    $profile = (object) ['address_line_1' => 'Suhaim Bin Hamad Street', 'address_line_2' => 'address_line_2', 'address_line_3' => 'Doha', 'mobile' => '+974 4001 1911'];

    expect(($this->call)('branchRow', $branch, $profile))->toMatchArray([
        'id' => 4,
        'tenant_id' => $this->tenant->id,
        'name' => 'Dubai',
        'code' => 'D',
        'location' => 'Suhaim Bin Hamad Street, Doha',
        'mobile' => '+974 4001 1911',
    ]);
});
