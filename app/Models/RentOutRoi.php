<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * Return on investment owed to a sale (lease) agreement's buyer for a period.
 *
 * It is an expense of the business, the opposite of a service charge: saving
 * one credits the customer (Dr ROI Expense / Cr Customer) and paying it out is
 * a separate RoiPayment transaction (Dr Customer / Cr payment method).
 */
class RentOutRoi extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant;

    /** Transaction source of the accrual row (credits the customer). */
    public const SOURCE = 'ROI';

    /** Transaction source of a payout against the ROI balance. */
    public const PAYMENT_SOURCE = 'RoiPayment';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'date',
        'start_date',
        'end_date',
        'no_of_months',
        'sale_price',
        'per_month_roi',
        'amount',
        'description',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'sale_price' => 'decimal:2',
        'per_month_roi' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * ROI booked against an agreement and how much of it has been paid out,
     * read from the ledger rows so it always agrees with the journals.
     *
     * @return array{accrued: float, paid: float, balance: float}
     */
    public static function totalsFor(int $rentOutId): array
    {
        $totals = RentOutTransaction::query()
            ->where('rent_out_id', $rentOutId)
            ->whereIn('source', [self::SOURCE, self::PAYMENT_SOURCE])
            ->selectRaw('COALESCE(SUM(credit), 0) as accrued, COALESCE(SUM(debit), 0) as paid')
            ->first();

        $accrued = round((float) $totals->getAttribute('accrued'), 2);
        $paid = round((float) $totals->getAttribute('paid'), 2);

        return ['accrued' => $accrued, 'paid' => $paid, 'balance' => round($accrued - $paid, 2)];
    }

    /** @return BelongsTo<RentOut, $this> */
    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }
}
