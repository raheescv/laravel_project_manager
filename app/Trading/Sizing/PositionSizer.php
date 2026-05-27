<?php

namespace App\Trading\Sizing;

/**
 * R-based position sizing.
 *
 *   qty = (equity × risk_per_trade) ÷ (entry − stop)
 *
 * The number of shares you can take while losing no more than X% of
 * account equity if the trade hits its stop. Capped by available funds
 * and the MaxPositionSize risk-rule notional cap so RiskGate doesn't
 * reject after the fact.
 */
final class PositionSizer
{
    public function __construct(
        private readonly float $riskPerTrade = 0.01,
        private readonly float $maxNotional = 50_000.0,
    ) {}

    /**
     * @return array{quantity:int, notional:float, risk_amount:float, stop_distance:float}
     */
    public function size(float $equity, float $entry, float $stop, float $availableFunds): array
    {
        $stopDistance = $entry - $stop;
        if ($stopDistance <= 0 || $entry <= 0 || $equity <= 0) {
            return ['quantity' => 0, 'notional' => 0.0, 'risk_amount' => 0.0, 'stop_distance' => $stopDistance];
        }

        $riskAmount = $equity * $this->riskPerTrade;
        $rawQty = (int) floor($riskAmount / $stopDistance);

        $byFunds = (int) floor($availableFunds / $entry);
        $byCap = (int) floor($this->maxNotional / $entry);

        $qty = max(0, min($rawQty, $byFunds, $byCap));

        return [
            'quantity' => $qty,
            'notional' => $qty * $entry,
            'risk_amount' => $qty * $stopDistance,
            'stop_distance' => $stopDistance,
        ];
    }
}
