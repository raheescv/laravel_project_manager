<?php

namespace App\Support\Migration;

/**
 * Process-local flag marking that a bulk data migration (MigrateDataCommand / MigrateSalesChunkJob)
 * is replaying records in this process. It lets the shared sale pipeline drop the two things that
 * serialise parallel workers on hot products — the per-line product-cost recompute
 * (LogInventoryAction) and out-of-stock prevention (OutOfStockSales) — because during a bulk import
 * inventory.quantity is reconciled from the InventoryLog deltas AFTER the run instead of being kept
 * exact on every write.
 *
 * It is intentionally an in-process static (not config/cache): each queue worker enables it at the
 * start of a migration job, so it only affects that worker while it is replaying migration data and
 * never leaks into normal request/live-sale traffic.
 */
class BulkImport
{
    protected static bool $enabled = false;

    /**
     * Separate, independently togglable flag: this process is replaying HISTORICAL records that
     * carry their own original date (migrated sales, sale returns), rather than ringing up new
     * ones. It gates the live till-session binding in Sale/SaleReturn::creating, which would
     * otherwise re-date every replayed record to the branch's currently open session and fold its
     * payments into that session's cash reconciliation.
     *
     * It is NOT folded into enable() because MigrateDataCommand replays purchases/returns/transfers
     * inline and still wants the normal per-line cost bookkeeping - it only needs the session
     * binding suppressed.
     */
    protected static bool $historicalReplay = false;

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }

    public static function enableHistoricalReplay(): void
    {
        self::$historicalReplay = true;
    }

    public static function disableHistoricalReplay(): void
    {
        self::$historicalReplay = false;
    }

    public static function replayingHistory(): bool
    {
        return self::$historicalReplay;
    }
}
