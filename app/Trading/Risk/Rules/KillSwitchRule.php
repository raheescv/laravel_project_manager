<?php

namespace App\Trading\Risk\Rules;

use App\Trading\Contracts\RiskRule;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;
use Illuminate\Support\Facades\Cache;

/**
 * Single-key kill switch. Operator-controlled override that blocks all new
 * BUY orders. SELL/FLATTEN are always allowed so we can exit safely.
 */
class KillSwitchRule implements RiskRule
{
    public const CACHE_KEY = 'trading:kill_switch';

    public function code(): string
    {
        return 'kill_switch';
    }

    public function check(OrderRequest $request, array $context = []): RiskDecision
    {
        if ($request->side !== OrderRequest::SIDE_BUY) {
            return RiskDecision::approve('kill-switch only blocks buys');
        }

        if (Cache::get(self::CACHE_KEY)) {
            return RiskDecision::block(
                reason: 'Kill switch is engaged',
                severity: 'breaker',
                ruleCode: $this->code(),
            );
        }

        return RiskDecision::approve();
    }

    public static function engage(?string $reason = null): void
    {
        Cache::forever(self::CACHE_KEY, ['engaged_at' => now()->toIso8601String(), 'reason' => $reason]);
    }

    public static function disengage(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function isEngaged(): bool
    {
        return (bool) Cache::get(self::CACHE_KEY);
    }
}
