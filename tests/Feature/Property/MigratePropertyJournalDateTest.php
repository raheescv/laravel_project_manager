<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;
use App\Models\Tenant;

/**
 * The rent-out data migration posts each old journal with the date it carried
 * in accounts - on the header and on both entry legs - never the run date.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->command = new MigratePropertyDataCommand();
    (fn ($id) => $this->tenantId = $id)->call($this->command, $this->tenant->id);

    $this->row = (object) [
        'id' => 501,
        'rentout_id' => 12,
        'date' => '2023-03-15',
        'remark' => 'March rent',
        'voucher_no' => 77,
        'created_at' => '2023-03-20 09:10:00',
        'updated_at' => '2023-03-20 09:10:00',
    ];
    $this->transaction = ['group' => 'Rent Payment', 'created_by' => null, 'cheque_no' => null, 'bank_name' => null, 'cheque_date' => null];
});

it('dates the journal header as the old journal', function (bool $isMoneyIn): void {
    $journal = (fn () => $this->journalFor(test()->row, $isMoneyIn, 1, test()->transaction))->call($this->command);

    expect($journal)->toMatchArray([
        'id' => 501,
        'date' => '2023-03-15',
        'created_at' => '2023-03-20 09:10:00',
    ]);
})->with(['receipt' => true, 'payout' => false]);

it('dates both entry legs as the old journal', function (bool $isMoneyIn): void {
    $entries = (fn () => $this->entryPairFor(test()->row, 10, 20, 1500.0, $isMoneyIn, 1, test()->transaction))->call($this->command);

    expect($entries)->toHaveCount(2)
        ->and(array_column($entries, 'date'))->toBe(['2023-03-15', '2023-03-15']);
})->with(['receipt' => true, 'payout' => false]);
