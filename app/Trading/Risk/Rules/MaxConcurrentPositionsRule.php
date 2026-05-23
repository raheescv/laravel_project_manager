<?php

namespace App\Trading\Risk\Rules;

use App\Trading\Contracts\RiskRule;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;

class MaxConcurrentPositionsRule implements RiskRule
{
    public function __construct(private int $maxConcurrent = 10) {}

    public function code(): string
    {
        return 'max_concurrent_positions';
    }

    public function check(OrderRequest $request, array $context = []): RiskDecision
    {
        if ($request->side !== OrderRequest::SIDE_BUY) {
            return RiskDecision::approve('non-buy bypass');
        }

        $cap = (int) ($context['max_concurrent_positions'] ?? $this->maxConcurrent);
        $current = (int) ($context['open_positions_count'] ?? 0);

        if ($current >= $cap) {
            return RiskDecision::block(
                reason: "Already holding {$current} positions, cap is {$cap}",
                severity: 'blocked',
                details: ['current' => $current, 'cap' => $cap],
                ruleCode: $this->code(),
            );
        }

        return RiskDecision::approve('within concurrent positions cap');
    }
}
