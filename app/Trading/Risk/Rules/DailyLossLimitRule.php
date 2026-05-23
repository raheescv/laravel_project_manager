<?php

namespace App\Trading\Risk\Rules;

use App\Models\TradingCircuitState;
use App\Trading\Contracts\RiskRule;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;

/**
 * If today's realized loss exceeds the limit, no new buys are allowed.
 * Sells (and FLATTEN signals) are always permitted so the operator can
 * actually exit positions when the circuit has tripped.
 */
class DailyLossLimitRule implements RiskRule
{
    public function __construct(private float $maxDailyLoss = 5000.0) {}

    public function code(): string
    {
        return 'daily_loss_limit';
    }

    public function check(OrderRequest $request, array $context = []): RiskDecision
    {
        if ($request->side !== OrderRequest::SIDE_BUY) {
            return RiskDecision::approve('sell side bypasses daily loss gate');
        }

        $limit = (float) ($context['max_daily_loss'] ?? $this->maxDailyLoss);
        $state = TradingCircuitState::firstWhere('trading_day', now()->toDateString());

        $realized = (float) ($state->realized_pnl ?? 0);

        if ($state && $state->breaker_tripped) {
            return RiskDecision::block(
                reason: 'Circuit breaker tripped: '.($state->trip_reason ?? 'unspecified'),
                severity: 'breaker',
                details: ['realized' => $realized],
                ruleCode: $this->code(),
            );
        }

        if ($realized <= -1 * abs($limit)) {
            return RiskDecision::block(
                reason: "Daily loss ₹{$realized} reached the limit (₹{$limit})",
                severity: 'breaker',
                details: ['realized' => $realized, 'limit' => $limit],
                ruleCode: $this->code(),
            );
        }

        return RiskDecision::approve('within daily loss budget');
    }
}
