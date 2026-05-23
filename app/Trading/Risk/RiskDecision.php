<?php

namespace App\Trading\Risk;

/**
 * The result of running a RiskRule (or the whole RiskGate) against an order.
 */
final class RiskDecision
{
    public function __construct(
        public readonly bool $approved,
        public readonly string $reason = '',
        public readonly string $severity = 'info',
        public readonly array $details = [],
        public readonly ?string $ruleCode = null,
    ) {}

    public static function approve(string $reason = 'ok', array $details = []): self
    {
        return new self(approved: true, reason: $reason, details: $details);
    }

    public static function block(string $reason, string $severity = 'blocked', array $details = [], ?string $ruleCode = null): self
    {
        return new self(approved: false, reason: $reason, severity: $severity, details: $details, ruleCode: $ruleCode);
    }
}
