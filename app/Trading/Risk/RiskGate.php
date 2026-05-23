<?php

namespace App\Trading\Risk;

use App\Models\TradingCircuitState;
use App\Models\TradingRiskEvent;
use App\Trading\Contracts\RiskRule;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\Rules\DailyLossLimitRule;
use App\Trading\Risk\Rules\KillSwitchRule;
use App\Trading\Risk\Rules\MaxConcurrentPositionsRule;
use App\Trading\Risk\Rules\MaxPositionSizeRule;
use App\Trading\Risk\Rules\SymbolCooldownRule;

/**
 * The single pre-trade gate. Every order — live or paper — must pass
 * through here before reaching a broker adapter.
 *
 * Rule order matters: cheap/fast checks first, breaker checks last so
 * we log the most useful reason on a block.
 */
class RiskGate
{
    /** @var RiskRule[] */
    private array $rules;

    public function __construct(array $rules = [])
    {
        $this->rules = $rules ?: $this->defaultRules();
    }

    public function defaultRules(): array
    {
        return [
            new KillSwitchRule(),
            new SymbolCooldownRule(cooldownMinutes: (int) config('trading.risk.cooldown_minutes', 15)),
            new MaxPositionSizeRule(maxNotional: (float) config('trading.risk.max_position_size', 50000)),
            new MaxConcurrentPositionsRule(maxConcurrent: (int) config('trading.risk.max_concurrent_positions', 10)),
            new DailyLossLimitRule(maxDailyLoss: (float) config('trading.risk.max_daily_loss', 5000)),
        ];
    }

    public function evaluate(OrderRequest $request, array $context = []): RiskDecision
    {
        foreach ($this->rules as $rule) {
            $decision = $rule->check($request, $context);
            if (! $decision->approved) {
                $this->record($rule, $request, $decision, $context);

                return $decision;
            }
        }

        return RiskDecision::approve('all rules passed');
    }

    public function tripBreaker(string $reason, array $context = []): void
    {
        $today = now()->toDateString();
        $state = TradingCircuitState::firstOrCreate(['trading_day' => $today]);
        $state->breaker_tripped = true;
        $state->trip_reason = $reason;
        $state->tripped_at = now();
        $state->save();

        TradingRiskEvent::create([
            'rule_code' => 'circuit_breaker',
            'severity' => 'breaker',
            'message' => $reason,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    public function rules(): array
    {
        return $this->rules;
    }

    private function record(RiskRule $rule, OrderRequest $request, RiskDecision $decision, array $context): void
    {
        TradingRiskEvent::create([
            'rule_code' => $rule->code(),
            'severity' => $decision->severity,
            'symbol' => $request->symbol,
            'strategy_code' => $request->strategyCode,
            'message' => $decision->reason,
            'context' => array_merge($decision->details, ['order' => $request->toArray()]),
            'occurred_at' => now(),
        ]);
    }
}
