<?php

use App\Models\Sale;
use App\Support\Audit\AuditChanges;
use OwenIt\Auditing\Facades\Auditor;
use OwenIt\Auditing\Models\Audit;

/**
 * Money columns come back from MySQL as "0.00" while forms send 0, so Eloquent
 * sees them as dirty. Those number-format-only changes must never be stored.
 */
function auditedSale(array $original, array $changes): Sale
{
    $sale = (new Sale())->setRawAttributes(['id' => 987654, ...$original], true);
    $sale->exists = true;
    $sale->forceFill($changes);
    $sale->setAuditEvent('updated');

    Auditor::execute($sale);

    return $sale;
}

it('does not store numeric values that only differ in format', function (): void {
    auditedSale(
        ['other_discount' => '0.00', 'freight' => '0.00', 'paid' => '0.00'],
        ['other_discount' => 0, 'freight' => '0', 'paid' => 26],
    );

    $audit = Audit::query()->where('auditable_type', Sale::class)->where('auditable_id', 987654)->sole();

    expect($audit->old_values)->toBe(['paid' => '0.00'])
        ->and($audit->new_values)->toBe(['paid' => 26]);
});

it('skips the update audit entirely when nothing really changed', function (): void {
    auditedSale(
        ['freight' => '0.00', 'round_off' => '1.50'],
        ['freight' => 0, 'round_off' => 1.5],
    );

    expect(Audit::query()->where('auditable_type', Sale::class)->where('auditable_id', 987654)->exists())->toBeFalse();
});

it('treats only equal numbers as equivalent', function (mixed $old, mixed $new, bool $equivalent): void {
    expect(AuditChanges::isEquivalent($old, $new))->toBe($equivalent);
})->with([
    ['0.00', 0, true],
    ['26.000', '26', true],
    ['0.00', null, false],
    ['', 0, false],
    ['1.50', 1.51, false],
    ['abc', 'abc', false],
]);
