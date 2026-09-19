<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The column layout of one vendor's invoice, learned from the first scan.
 *
 * @see database/migrations/2026_09_19_000001_create_purchase_scan_templates_table.php
 */
class PurchaseScanTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'source',
        'bands',
        'mapping',
        'match_by',
        'default_tax',
        'used_count',
        'last_used_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bands' => 'array',
        'mapping' => 'array',
        'default_tax' => 'float',
        'last_used_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Does a freshly scanned invoice have the same columns this template was
     * saved for?
     *
     * Vendors do change their stationery, and a saved mapping applied to a
     * different layout would put the MRP in the cost column without anyone
     * noticing. Same number of columns, each sitting within a hair of where it
     * used to, or the user maps it again.
     */
    public function fits(array $bands, float $tolerance = 6.0): bool
    {
        $saved = $this->bands ?? [];

        if (count($saved) !== count($bands)) {
            return false;
        }

        foreach ($bands as $index => $band) {
            if (abs($band['x0'] - $saved[$index]['x0']) > $tolerance || abs($band['x1'] - $saved[$index]['x1']) > $tolerance) {
                return false;
            }
        }

        return true;
    }
}
