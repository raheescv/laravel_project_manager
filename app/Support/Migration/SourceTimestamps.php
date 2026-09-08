<?php

namespace App\Support\Migration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Restores a replayed record's ORIGINAL created_at / updated_at from its mysql2 source row.
 *
 * The migration replays history through the normal create actions, so every row is stamped with
 * now(): a decade of sales, purchases and returns would all look like they happened on migration
 * day. That mis-sorts every listing that orders by created_at, and mis-feeds sale:sync-day-sessions,
 * which re-points sales at the session covering their created_at.
 *
 * Written as a raw table update on purpose — no model event, no audit row, and no automatic
 * updated_at refresh may fire while we are back-stamping.
 */
class SourceTimestamps
{
    /**
     * @param  object  $source  The mysql2 row the model was built from.
     */
    public static function apply(Model $model, object $source): void
    {
        $values = array_filter([
            'created_at' => $source->created_at ?? null,
            'updated_at' => $source->updated_at ?? null,
        ]);

        if (! $values) {
            return;
        }

        DB::table($model->getTable())->where('id', $model->getKey())->update($values);
    }
}
