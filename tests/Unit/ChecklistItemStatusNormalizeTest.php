<?php

use App\Enums\RentOut\ChecklistItemStatus;
use App\Enums\RentOut\ChecklistPhase;

/**
 * The per-phase status rule shared by the web checklist tab (SaveAction) and the
 * technician app: Move-In is binary — present or blank — while Move-Out keeps
 * any recognised status.
 */
it('normalises a status for its phase', function (ChecklistPhase $phase, mixed $value, ?string $expected): void {
    expect(ChecklistItemStatus::normalizeFor($phase, $value))->toBe($expected);
})->with([
    'move-in present' => [ChecklistPhase::MoveIn, 'ok', 'ok'],
    'move-in damaged is not a move-in state' => [ChecklistPhase::MoveIn, 'not_ok', null],
    'move-in blank' => [ChecklistPhase::MoveIn, null, null],
    'move-out good' => [ChecklistPhase::MoveOut, 'ok', 'ok'],
    'move-out damaged' => [ChecklistPhase::MoveOut, 'not_ok', 'not_ok'],
    'move-out unknown' => [ChecklistPhase::MoveOut, 'broken', null],
    'move-out blank' => [ChecklistPhase::MoveOut, '', null],
]);
