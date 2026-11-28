<?php

use App\Console\Commands\SingleUse\RealEstate\MigratePropertyDataCommand;

/**
 * accounts lists every Services / Management Fee journal on its Services report,
 * even when the journal carries a stale utility_term_id, so the migration files
 * those as Service transactions - only an unlabelled journal follows the utility link.
 */
beforeEach(function (): void {
    $this->command = new MigratePropertyDataCommand();
    $this->sourceFor = fn (object $row): array => (fn () => $this->transactionSourceFor(
        $row,
        (object) ['agreement_type' => 'rental'],
        ['head' => 1, 'counter' => 16, 'direction' => 'in'],
        collect(),
        collect([1106 => '2024-02-01']),
        collect(),
        in_array($row->payment_type, ['Services', 'Management Fee'], true),
        true,
    ))->call($this->command);
});

it('files a service payment with a stale utility link as a service', function (string $paymentType): void {
    $source = ($this->sourceFor)((object) ['payment_term_id' => 0, 'utility_term_id' => 1106, 'payment_type' => $paymentType]);

    expect($source)->toBe(['source' => 'Service', 'group' => 'Service Payment', 'payment_type' => 'Services']);
})->with(['services' => 'Services', 'management fee' => 'Management Fee']);

it('still files a utility payment against its utility term', function (): void {
    $source = ($this->sourceFor)((object) ['payment_term_id' => 0, 'utility_term_id' => 1106, 'payment_type' => 'Utility']);

    expect($source)->toMatchArray(['source' => 'UtilityTerm', 'model' => 'RentOutUtilityTerm', 'model_id' => 1106, 'due_date' => '2024-02-01']);
});
