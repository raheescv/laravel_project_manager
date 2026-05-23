<?php

namespace App\Trading\Risk\Rules;

use App\Trading\Contracts\RiskRule;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;
use Illuminate\Support\Facades\Cache;

/**
 * After a stop-loss exit on a symbol, refuse to re-enter for N minutes.
 * Prevents whipsaw on choppy names.
 */
class SymbolCooldownRule implements RiskRule
{
    public function __construct(private int $cooldownMinutes = 15) {}

    public function code(): string
    {
        return 'symbol_cooldown';
    }

    public function check(OrderRequest $request, array $context = []): RiskDecision
    {
        if ($request->side !== OrderRequest::SIDE_BUY) {
            return RiskDecision::approve('sells skip cooldown');
        }

        $minutes = (int) ($context['cooldown_minutes'] ?? $this->cooldownMinutes);
        $key = self::cacheKey($request->symbol);
        $expiresAt = Cache::get($key);

        if ($expiresAt && now()->getTimestamp() < $expiresAt) {
            $remaining = $expiresAt - now()->getTimestamp();

            return RiskDecision::block(
                reason: "Symbol {$request->symbol} on cooldown for {$remaining}s",
                severity: 'warning',
                details: ['remaining_seconds' => $remaining],
                ruleCode: $this->code(),
            );
        }

        return RiskDecision::approve();
    }

    public static function trip(string $symbol, int $minutes = 15): void
    {
        Cache::put(self::cacheKey($symbol), now()->addMinutes($minutes)->getTimestamp(), now()->addMinutes($minutes + 1));
    }

    private static function cacheKey(string $symbol): string
    {
        return 'trading:cooldown:'.strtoupper($symbol);
    }
}
