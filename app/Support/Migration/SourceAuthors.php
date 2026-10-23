<?php

namespace App\Support\Migration;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Restores a replayed record's ORIGINAL created_by / updated_by from its mysql2 source row.
 *
 * The migration replays history through the normal create actions, which stamp created_by with
 * the acting user — the system user the replay runs as. Every migrated sale would then read
 * "Created By: system" instead of the cashier who rang it up in the old system.
 *
 * The acting user can't simply be swapped for the real author: the create actions also check that
 * user's max_discount_per_sale, which would reject historical sales. So the replay keeps running as
 * the system user and the real authors are back-stamped afterwards with raw table updates — no
 * model event, audit row or updated_at refresh fires while we do it.
 */
class SourceAuthors
{
    /**
     * @param  int|null  $createdBy  Target users.id of the source row's author; null keeps the replay's value.
     * @param  int|null  $updatedBy  Target users.id of the source row's last editor; null keeps the replay's value.
     */
    public static function apply(Model $model, ?int $createdBy, ?int $updatedBy): void
    {
        $values = array_filter([
            'created_by' => $createdBy,
            'updated_by' => $updatedBy,
        ]);

        if (! $values) {
            return;
        }

        DB::table($model->getTable())->where('id', $model->getKey())->update($values);
    }

    /**
     * Back-stamps a replayed sale AND its lines: the item create action stamps every line with the
     * replay's system user too. Old sale lines carry no author of their own, so they take the sale's.
     */
    public static function applyToSale(Sale $sale, ?int $createdBy, ?int $updatedBy): void
    {
        self::apply($sale, $createdBy, $updatedBy);

        $values = array_filter([
            'created_by' => $createdBy,
            'updated_by' => $updatedBy,
        ]);

        if ($values) {
            DB::table((new SaleItem())->getTable())->where('sale_id', $sale->getKey())->update($values);
        }
    }

    /**
     * Maps an old-system author to the target users.id. The old system keeps users and employees in
     * separate tables whose ids overlap (users.id 10 "Habid", employees.id 10 "MAKBUL"), and its
     * sales say which one wrote the row in created_model / updated_model. Both land in the target
     * users table, told apart by type, so the id must be looked up in the map of that model.
     *
     * @param  string|null  $sourceModel  The old row's created_model / updated_model ("Employee" | "User").
     * @param  Collection<int|string, int>  $userMap  source users.id => target users.id
     * @param  Collection<int|string, int>  $employeeMap  source employees.id => target users.id
     */
    public static function resolve(?string $sourceModel, int|string|null $sourceId, Collection $userMap, Collection $employeeMap): ?int
    {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        $map = strcasecmp((string) $sourceModel, 'Employee') === 0 ? $employeeMap : $userMap;

        return $map[$sourceId] ?? null;
    }

    /**
     * Re-points already-migrated sales (matched by invoice_no) and their lines at their source
     * authors. Sales whose source author was never migrated keep what they have.
     *
     * @param  Collection<int|string, int>  $userMap  source users.id => target users.id
     * @param  Collection<int|string, int>  $employeeMap  source employees.id => target users.id
     * @param  Collection<int, object{invoice_no: ?string, created_model: ?string, created_by: ?int, updated_model: ?string, updated_by: ?int}>  $sourceSales
     * @return int Target sales updated.
     */
    public static function restoreSales(Collection $userMap, Collection $employeeMap, Collection $sourceSales): int
    {
        $updated = 0;

        $sourceSales
            ->filter(fn ($sale) => $sale->invoice_no !== null && $sale->invoice_no !== '')
            ->map(fn ($sale) => [
                'invoice_no' => $sale->invoice_no,
                'values' => array_filter([
                    'created_by' => self::resolve($sale->created_model ?? null, $sale->created_by, $userMap, $employeeMap),
                    'updated_by' => self::resolve($sale->updated_model ?? null, $sale->updated_by, $userMap, $employeeMap),
                ]),
            ])
            ->filter(fn ($sale) => $sale['values'] !== [])
            ->groupBy(fn ($sale) => json_encode($sale['values']))
            ->each(function (Collection $sales) use (&$updated): void {
                $values = $sales->first()['values'];
                foreach ($sales->pluck('invoice_no')->chunk(1000) as $invoiceNos) {
                    $targetSales = Sale::withTrashed()
                        ->withoutGlobalScope(AssignedBranchScope::class)
                        ->whereIn('invoice_no', $invoiceNos->values()->all());

                    SaleItem::withTrashed()->whereIn('sale_id', (clone $targetSales)->select('id'))->toBase()->update($values);
                    $updated += $targetSales->toBase()->update($values);
                }
            });

        return $updated;
    }

    /**
     * Puts back each migrated user's name exactly as the old system spells it ("HASRE", "Harshid VT"):
     * the users / employees steps used to re-case them as "Hasre" / "Harshid vt". Call once per
     * source table, with that table's map.
     *
     * @param  Collection<int|string, int>  $userMap  source id => target users.id
     * @param  Collection<int|string, string>  $sourceUserNames  source id => name
     * @return int Users updated.
     */
    public static function restoreUserNames(Collection $userMap, Collection $sourceUserNames): int
    {
        $renamed = 0;

        foreach ($sourceUserNames as $sourceId => $name) {
            if (! isset($userMap[$sourceId]) || $name === null || $name === '') {
                continue;
            }

            $renamed += User::whereKey($userMap[$sourceId])->toBase()->update(['name' => $name]);
        }

        return $renamed;
    }
}
