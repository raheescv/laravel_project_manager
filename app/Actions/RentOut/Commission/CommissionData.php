<?php

namespace App\Actions\RentOut\Commission;

use App\Models\RentOut;
use App\Models\RentOutCommission;
use Exception;

/**
 * Shapes the commission form into stored columns: the agent is always the
 * agreement's salesman, the contract amount defaults to the agreement price,
 * and the commission is always recalculated server-side.
 */
class CommissionData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalise(RentOut $rentOut, array $data): array
    {
        $type = ($data['commission_type'] ?? RentOutCommission::TYPE_PERCENTAGE) === RentOutCommission::TYPE_FIXED
            ? RentOutCommission::TYPE_FIXED
            : RentOutCommission::TYPE_PERCENTAGE;

        $contractAmount = is_numeric($data['contract_amount'] ?? null) ? (float) $data['contract_amount'] : (float) $rentOut->rent;
        $percentage = $type === RentOutCommission::TYPE_PERCENTAGE ? (float) ($data['commission_percentage'] ?? 0) : 0;

        if (! $rentOut->salesman_id) {
            throw new Exception('Set a salesman on this agreement before adding a commission.', 1);
        }

        return [
            'rent_out_id' => $rentOut->id,
            'agent_id' => $rentOut->salesman_id,
            'date' => ($data['date'] ?? null) ?: now()->format('Y-m-d'),
            'commission_type' => $type,
            'commission_percentage' => $percentage,
            'contract_amount' => round($contractAmount, 2),
            'amount' => RentOutCommission::calculate($type, $contractAmount, $percentage, (float) ($data['amount'] ?? 0)),
            'remark' => $data['remark'] ?? null,
        ];
    }
}
