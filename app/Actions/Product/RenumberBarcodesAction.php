<?php

namespace App\Actions\Product;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\UniqueNoCounter;
use App\Services\TenantService;
use App\Support\UniqueNoCounters;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Renumbers the system barcode series from a new start number, keeping the
 * barcodes in their current order, and moves the barcode counters to the last
 * number given out.
 *
 * Each distinct barcode is mapped once into a temporary table, then every
 * barcode table is rewritten with a single UPDATE ... JOIN. A product and the
 * inventory rows sharing its barcode therefore receive the same new number, and
 * the cost stays a handful of statements however many barcodes there are.
 *
 * The raw UPDATE skips the model events that write audits, so the audit rows of
 * the audited models are inserted the same set-based way, just before it.
 */
class RenumberBarcodesAction
{
    private const MAP_TABLE = 'barcode_renumber_map';

    public const AUDIT_TAG = 'barcode-renumber';

    /** Barcode tables whose models are audited. */
    private const AUDITED_MODELS = [
        'products' => Product::class,
        'inventories' => Inventory::class,
    ];

    public function execute(int $startNumber): array
    {
        try {
            if ($startNumber < 1) {
                throw new Exception('The new series must start at 1 or higher.');
            }

            $data = DB::transaction(function () use ($startNumber): array {
                $ceiling = (int) UniqueNoCounter::query()->where('segment', UniqueNoCounters::BARCODE)->lockForUpdate()->pluck('number')->max();

                if ($ceiling < 1) {
                    throw new Exception('No barcodes have been issued yet, so there is no series to renumber.');
                }

                $this->createMap($startNumber, $ceiling);

                try {
                    $count = DB::table(self::MAP_TABLE)->count();

                    if ($count === 0) {
                        throw new Exception('No barcodes were found in the current series.');
                    }

                    $lastNumber = $startNumber + $count - 1;
                    $this->guardAgainstCollisions($startNumber, $lastNumber, $ceiling);

                    $tenantId = (int) app(TenantService::class)->getCurrentTenantId();
                    foreach (UniqueNoCounters::BARCODE_TABLES as $table) {
                        if (isset(self::AUDITED_MODELS[$table])) {
                            $this->writeAudits($table, (new (self::AUDITED_MODELS[$table]))->getMorphClass(), $tenantId);
                        }

                        DB::update("UPDATE {$table} target JOIN ".self::MAP_TABLE.' map ON map.old_number = target.barcode_number SET target.barcode_number = map.new_number WHERE target.tenant_id = ? AND map.old_number <> map.new_number', [$tenantId]);
                    }

                    UniqueNoCounter::query()->where('segment', UniqueNoCounters::BARCODE)->update(['number' => $lastNumber]);
                } finally {
                    DB::statement('DROP TEMPORARY TABLE IF EXISTS '.self::MAP_TABLE);
                }

                return ['count' => $count, 'first' => $startNumber, 'last' => $lastNumber];
            });

            $return['success'] = true;
            $return['message'] = "Renumbered {$data['count']} barcodes from {$data['first']} to {$data['last']}";
            $return['data'] = $data;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /** Map every barcode in the series to its new number, ordered by its current value. */
    private function createMap(int $startNumber, int $ceiling): void
    {
        $connection = DB::connection();
        $charset = $connection->getConfig('charset') ?: 'utf8mb4';
        $collation = $connection->getConfig('collation') ?: 'utf8mb4_unicode_ci';
        [$seriesSql, $bindings] = UniqueNoCounters::barcodeSeriesSql($ceiling);

        DB::statement('DROP TEMPORARY TABLE IF EXISTS '.self::MAP_TABLE);
        DB::statement('CREATE TEMPORARY TABLE '.self::MAP_TABLE." (old_number VARCHAR(191) CHARACTER SET {$charset} COLLATE {$collation} NOT NULL PRIMARY KEY, new_number VARCHAR(191) CHARACTER SET {$charset} COLLATE {$collation} NOT NULL)");
        DB::insert('INSERT INTO '.self::MAP_TABLE." (old_number, new_number) SELECT barcode_number, ? + ROW_NUMBER() OVER (ORDER BY CAST(barcode_number AS UNSIGNED), barcode_number) - 1 FROM ({$seriesSql}) series", [$startNumber, ...$bindings]);
    }

    /** One "updated" audit per row whose barcode changes, as the Auditable trait would have written it. */
    private function writeAudits(string $table, string $auditableType, int $tenantId): void
    {
        $user = Auth::user();
        $now = now();

        DB::insert(
            'INSERT INTO audits (user_type, user_id, event, auditable_type, auditable_id, old_values, new_values, url, ip_address, user_agent, tags, created_at, updated_at) '
            ."SELECT ?, ?, 'updated', ?, target.id, JSON_OBJECT('barcode_number', map.old_number), JSON_OBJECT('barcode_number', map.new_number), ?, ?, ?, ?, ?, ? "
            ."FROM {$table} target JOIN ".self::MAP_TABLE.' map ON map.old_number = target.barcode_number '
            .'WHERE target.tenant_id = ? AND map.old_number <> map.new_number',
            [
                $user?->getMorphClass(),
                $user?->getAuthIdentifier(),
                $auditableType,
                app()->runningInConsole() ? 'console' : request()->fullUrl(),
                request()->ip(),
                Str::limit((string) request()->userAgent(), 1020, ''),
                self::AUDIT_TAG,
                $now,
                $now,
                $tenantId,
            ],
        );
    }

    /** The new range may not reach a numeric barcode that sits outside the series. */
    private function guardAgainstCollisions(int $startNumber, int $lastNumber, int $ceiling): void
    {
        $from = max($startNumber, $ceiling + 1);

        if ($from > $lastNumber) {
            return;
        }

        $tenantId = (int) app(TenantService::class)->getCurrentTenantId();

        foreach (UniqueNoCounters::BARCODE_TABLES as $table) {
            $clash = DB::table($table)
                ->where('tenant_id', $tenantId)
                ->whereRaw("barcode_number REGEXP '^[0-9]{1,18}$'")
                ->whereRaw('CAST(barcode_number AS UNSIGNED) BETWEEN ? AND ?', [$from, $lastNumber])
                ->value('barcode_number');

            if ($clash !== null) {
                throw new Exception("Barcode {$clash} is already in use outside the series. Choose a start number so the new series ends below it.");
            }
        }
    }
}
