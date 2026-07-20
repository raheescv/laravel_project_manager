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
}
