<?php

namespace App\Trading\Risk\Rules;

use App\Trading\Contracts\RiskRule;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;

class MaxPositionSizeRule implements RiskRule
{
    public function __construct(private float $maxNotional = 50000.0) {}

    public function code(): string
    {
        return 'max_position_size';
    }

    public function check(OrderRequest $request, array $context = []): RiskDecision
    {
        $cap = (float) ($context['max_position_size'] ?? $this->maxNotional);
        $notional = $request->notionalValue();

        if ($notional > 0 && $notional > $cap) {
            return RiskDecision::block(
                reason: "Order notional ₹{$notional} exceeds max position size ₹{$cap}",
                severity: 'blocked',
                details: ['notional' => $notional, 'cap' => $cap],
                ruleCode: $this->code(),
            );
        }

        return RiskDecision::approve('within position cap');
    }
}
