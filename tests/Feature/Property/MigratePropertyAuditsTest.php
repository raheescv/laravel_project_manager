<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Models\Account;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use App\Models\Tenant;
use App\Models\User;

/**
 * The rent-out data migration copies accounts' audit history onto the models
 * the records became, in the new tables' column names and values.
 */
function migrationAuditMap(MigratePropertyDataCommand $command, string $oldType): array
{
    return (fn () => $this->auditMaps()['App\\Models\\'.$oldType])->call($command);
}

function migrateAudit(MigratePropertyDataCommand $command, string $oldType, array $audit): ?array
{
    $map = migrationAuditMap($command, $oldType);

    return (fn () => $this->auditRow((object) ($audit + [
        'user_type' => 'App\\Models\\User',
        'user_id' => 182,
        'event' => 'updated',
        'old_values' => '[]',
        'new_values' => '[]',
        'url' => 'https://accounts.test/rentout',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'created_at' => '2026-10-29 19:05:29',
        'updated_at' => '2026-10-29 19:05:29',
    ]), $map))->call($command);
}

/**
 * @param  array<string, string>  $ids  target table => existing id
 */
function seedAuditTargets(MigratePropertyDataCommand $command, array $ids): void
{
    (function () use ($ids) {
        foreach ($ids as $table => $id) {
            $this->auditTargetIds[$table][$id] = true;
        }
    })->call($command);
}

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->command = new MigratePropertyDataCommand();
    (fn ($id) => $this->tenantId = $id)->call($this->command, $this->tenant->id);
});

it('renames rent out columns and translates their values', function (): void {
    (function () {
        $this->accountByRef = [1809 => 55];
        $this->paymentModeMap = [1 => 'cash', 7 => 'cheque'];
    })->call($this->command);
    seedAuditTargets($this->command, ['rent_outs' => 1938]);

    $row = migrateAudit($this->command, 'Rentout', [
        'auditable_id' => 1938,
        'old_values' => json_encode(['customer_id' => 1, 'status' => 4, 'collection_payment_mode_id' => 1, 'purpose' => 'Residential', 'updated_at' => '2026-10-29 19:05:08']),
        'new_values' => json_encode(['customer_id' => '1809', 'status' => 1, 'collection_payment_mode_id' => 7, 'purpose' => 'Commercial', 'updated_at' => '2026-10-29 19:05:29']),
    ]);

    expect($row)->toMatchArray(['auditable_type' => RentOut::class, 'auditable_id' => 1938, 'tags' => 'accounts-migration'])
        ->and(json_decode($row['old_values'], true))->toBe(['account_id' => null, 'status' => 'booked', 'collection_payment_mode' => 'cash'])
        ->and(json_decode($row['new_values'], true))->toBe(['account_id' => 55, 'status' => 'occupied', 'collection_payment_mode' => 'cheque']);
});

it('reads every payment term value from its old key when names swap', function (): void {
    seedAuditTargets($this->command, ['rent_out_payment_terms' => 33072]);

    $row = migrateAudit($this->command, 'PaymentTerm', [
        'event' => 'created',
        'auditable_id' => 33072,
        'new_values' => json_encode(['rentout_id' => 1938, 'date' => '2026-10-29', 'rent' => '25000', 'discount' => 500, 'amount' => 24500, 'remark' => 'EOI', 'status' => 'x', 'customer_id' => 1809, 'id' => 33072]),
    ]);

    expect($row['auditable_type'])->toBe(RentOutPaymentTerm::class)
        ->and(json_decode($row['new_values'], true))->toBe([
            'rent_out_id' => 1938, 'due_date' => '2026-10-29', 'amount' => '25000', 'discount' => 500, 'total' => 24500, 'remarks' => 'EOI', 'id' => 33072,
        ]);
});

it('moves a customer audit onto its account under the new id', function (): void {
    (function () {
        $this->customerHeadById = [1649 => 1809];
        $this->accountByRef = [1809 => 55];
    })->call($this->command);
    seedAuditTargets($this->command, ['accounts' => 55]);

    $row = migrateAudit($this->command, 'Customer', [
        'event' => 'created',
        'auditable_id' => 1649,
        'new_values' => json_encode(['id' => 1649, 'whatsapp_no' => '77880144', 'nationality' => 'egyptian', 'billing_city_town' => '', 'dob' => '0000-00-00']),
    ]);

    expect($row)->toMatchArray(['auditable_type' => Account::class, 'auditable_id' => 55])
        ->and(json_decode($row['new_values'], true))->toBe(['id' => 55, 'whatsapp_mobile' => '77880144', 'nationality' => 'Egypt', 'dob' => null]);
});

it('moves an employee audit onto the migrated employee user', function (): void {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'employee', 'second_reference_no' => 'emp_158']);
    (fn () => $this->designationMap = [5 => 9])->call($this->command);
    seedAuditTargets($this->command, ['users' => $employee->id]);

    $row = migrateAudit($this->command, 'Employee', [
        'auditable_id' => 158,
        'old_values' => json_encode(['name' => 'Khaled', 'branch_id' => '1', 'designation_id' => '3', 'admin_flag' => 'No']),
        'new_values' => json_encode(['name' => 'Khaled Wertani', 'branch_id' => '2', 'designation_id' => '5', 'admin_flag' => 'Yes']),
    ]);

    expect($row)->toMatchArray(['auditable_type' => User::class, 'auditable_id' => $employee->id])
        ->and(json_decode($row['old_values'], true))->toBe(['name' => 'Khaled', 'default_branch_id' => '1', 'designation_id' => null])
        ->and(json_decode($row['new_values'], true))->toBe(['name' => 'Khaled Wertani', 'default_branch_id' => '2', 'designation_id' => 9]);
});

it('puts a journal amount on the side its payment was posted to', function (bool $isCredit, string $side): void {
    (function () use ($isCredit) {
        $this->creditTransactionIds = $isCredit ? [76915 => true] : [];
        $this->userByRef = [182 => 9];
    })->call($this->command);
    seedAuditTargets($this->command, ['rent_out_transactions' => 76915]);

    $row = migrateAudit($this->command, 'Journal', [
        'event' => 'created',
        'auditable_id' => 76915,
        'new_values' => json_encode(['rentout_id' => 1908, 'amount' => 33787, 'debit' => 1, 'credit' => 1776, 'voucher_no' => 74798, 'user_id' => 182, 'work_flow' => 'Rentout']),
    ]);

    expect($row['auditable_type'])->toBe(RentOutTransaction::class)
        ->and(json_decode($row['new_values'], true))->toBe(['rent_out_id' => 1908, $side => 33787, 'voucher_no' => 74798, 'created_by' => 9]);
})->with(['receipt' => [true, 'credit'], 'payout' => [false, 'debit']]);

it('skips audits of records that were never migrated', function (): void {
    (fn () => $this->auditTargetIds['rent_outs'] = [1938 => true])->call($this->command);

    expect(migrateAudit($this->command, 'Rentout', ['auditable_id' => 404, 'new_values' => json_encode(['remark' => 'x'])]))->toBeNull();
});

it('skips an update whose changes are not columns here', function (): void {
    seedAuditTargets($this->command, ['maintenances' => 11379]);

    expect(migrateAudit($this->command, 'Maintenance', [
        'auditable_id' => 11379,
        'old_values' => json_encode(['property_asset_supply_id' => null, 'updated_at' => '2026-03-25 10:43:25']),
        'new_values' => json_encode(['property_asset_supply_id' => 5, 'updated_at' => '2026-03-25 10:43:45']),
    ]))->toBeNull();
});

it('rewrites lead salesmen to their migrated users', function (): void {
    $salesman = User::factory()->create(['tenant_id' => $this->tenant->id, 'type' => 'employee', 'second_reference_no' => 'emp_161']);
    seedAuditTargets($this->command, ['property_leads' => 7]);

    $row = migrateAudit($this->command, 'PropertyLead', [
        'auditable_id' => 7,
        'old_values' => json_encode(['assigned_to' => '', 'type' => 'Sales']),
        'new_values' => json_encode(['assigned_to' => '161', 'type' => 'Leasing']),
    ]);

    expect(json_decode($row['new_values'], true))->toBe(['assigned_to' => $salesman->id, 'type' => 'Rentout'])
        ->and(json_decode($row['old_values'], true))->toBe(['assigned_to' => null, 'type' => 'Sales']);
});
