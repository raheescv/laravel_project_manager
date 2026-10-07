<?php

namespace App\Support;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Carbon\CarbonInterface;

/**
 * Where a rent-out's hand-over checklist stands: which phase is open, how many of
 * its signatures are in, and whether it has been sealed.
 *
 * A phase is *sealed* once every signatory role has signed it AND its actual
 * hand-over date is recorded — the technician app's "Seal hand-over" writes that
 * date, and a checklist completed entirely on the web reaches the same state.
 *
 * A rental runs two phases (move-in, then move-out). A lease/sale is a single
 * hand-over: its signatures sit under the move-in phase, its "Inspection Date"
 * is `actual_move_in_date` and its "Hand Over Date" is `actual_move_out_date` —
 * the same reading the web tab and the printed form give those columns.
 *
 * Reads the loaded `checklistSignatures` relation, so callers listing several
 * rent-outs should eager-load it.
 */
class RentOutChecklistState
{
    /** Move-out work is surfaced this many days before the agreement ends. */
    public const MOVE_OUT_LEAD_DAYS = 30;

    /** @return array<int, ChecklistPhase> the phases this agreement goes through */
    public static function phasesFor(RentOut $rentOut): array
    {
        return self::isSingleHandover($rentOut)
            ? [ChecklistPhase::MoveIn]
            : [ChecklistPhase::MoveIn, ChecklistPhase::MoveOut];
    }

    /** A lease/sale is handed over once — there is no move-out to inspect. */
    public static function isSingleHandover(RentOut $rentOut): bool
    {
        return $rentOut->agreement_type !== null && $rentOut->agreement_type !== AgreementType::Rental;
    }

    public static function hasPhase(RentOut $rentOut, ChecklistPhase $phase): bool
    {
        return in_array($phase, self::phasesFor($rentOut), true);
    }

    /** "Move-In" / "Move-Out" on a rental; the one hand-over of a lease/sale reads "Handover". */
    public static function phaseLabel(RentOut $rentOut, ChecklistPhase $phase): string
    {
        return self::isSingleHandover($rentOut) ? 'Handover' : $phase->label();
    }

    /** The rent-out column recording when [phase] was actually handed over. */
    public static function handoverDateColumn(RentOut $rentOut, ChecklistPhase $phase): string
    {
        return self::isSingleHandover($rentOut) ? 'actual_move_out_date' : $phase->actualDateColumn();
    }

    public static function signaturesRequired(): int
    {
        return count(ChecklistSignatoryRole::cases());
    }

    public static function signaturesDone(RentOut $rentOut, ChecklistPhase $phase): int
    {
        return collect(ChecklistSignatoryRole::cases())
            ->filter(fn (ChecklistSignatoryRole $role) => $rentOut->checklistSignatureFor($phase, $role)?->signature_path)
            ->count();
    }

    public static function isFullySigned(RentOut $rentOut, ChecklistPhase $phase): bool
    {
        return self::signaturesDone($rentOut, $phase) === self::signaturesRequired();
    }

    public static function isSealed(RentOut $rentOut, ChecklistPhase $phase): bool
    {
        return self::isFullySigned($rentOut, $phase) && $rentOut->{self::handoverDateColumn($rentOut, $phase)} !== null;
    }

    /** The tenant is on the way out: vacated, expired, a vacate date set, or the term about to end. */
    public static function isMoveOutDue(RentOut $rentOut): bool
    {
        if (in_array($rentOut->status, [RentOutStatus::Vacated, RentOutStatus::Expired], true) || $rentOut->vacate_date) {
            return true;
        }

        return $rentOut->end_date !== null && $rentOut->end_date->lte(now()->addDays(self::MOVE_OUT_LEAD_DAYS));
    }

    /** The phase still waiting for work, or null when nothing is due. */
    public static function openPhase(RentOut $rentOut): ?ChecklistPhase
    {
        if (! self::isSealed($rentOut, ChecklistPhase::MoveIn)) {
            return ChecklistPhase::MoveIn;
        }

        if (self::hasPhase($rentOut, ChecklistPhase::MoveOut) && self::isMoveOutDue($rentOut) && ! self::isSealed($rentOut, ChecklistPhase::MoveOut)) {
            return ChecklistPhase::MoveOut;
        }

        return null;
    }

    /** The phase to show when none was asked for: the open one, else the latest reached. */
    public static function defaultPhase(RentOut $rentOut): ChecklistPhase
    {
        return self::openPhase($rentOut)
            ?? (self::hasPhase($rentOut, ChecklistPhase::MoveOut) && self::isSealed($rentOut, ChecklistPhase::MoveIn) ? ChecklistPhase::MoveOut : ChecklistPhase::MoveIn);
    }

    /** [requested] when this agreement has that phase, otherwise its default. */
    public static function resolvePhase(RentOut $rentOut, ?ChecklistPhase $requested): ChecklistPhase
    {
        return $requested !== null && self::hasPhase($rentOut, $requested) ? $requested : self::defaultPhase($rentOut);
    }

    /**
     * The date a hand-over sits on: the actual hand-over date once recorded, else
     * when it is expected — the start date for a move-in / lease hand-over, the
     * vacate (or end) date for a move-out.
     */
    public static function scheduledDate(RentOut $rentOut, ChecklistPhase $phase): ?CarbonInterface
    {
        $actual = $rentOut->{self::handoverDateColumn($rentOut, $phase)};

        return $actual ?? ($phase === ChecklistPhase::MoveIn
            ? $rentOut->start_date
            : ($rentOut->vacate_date ?? $rentOut->end_date));
    }

    /**
     * The phases a rent-out has reached: move-in always, move-out once it is due
     * or already sealed. Each is one hand-over job in the technician's list.
     *
     * @return array<int, ChecklistPhase>
     */
    public static function reachedPhases(RentOut $rentOut): array
    {
        return array_values(array_filter(self::phasesFor($rentOut), fn (ChecklistPhase $phase) => $phase === ChecklistPhase::MoveIn
            || self::isMoveOutDue($rentOut)
            || self::isSealed($rentOut, $phase)));
    }
}
