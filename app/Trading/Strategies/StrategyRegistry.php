<?php

namespace App\Trading\Strategies;

use App\Models\TradingStrategy as StrategyModel;
use App\Trading\Contracts\Strategy;
use Illuminate\Support\Collection;

/**
 * Singleton registry of all known strategies.
 *
 * Service provider binds this and registers built-in strategies. Anywhere
 * that needs to enumerate/run strategies (live commands, backtester, UI)
 * pulls them from here so there is exactly one source of truth.
 */
class StrategyRegistry
{
    /** @var array<string, Strategy> */
    private array $strategies = [];

    public function register(Strategy $strategy): void
    {
        $this->strategies[$strategy->code()] = $strategy;
    }

    public function get(string $code): ?Strategy
    {
        return $this->strategies[$code] ?? null;
    }

    public function all(): Collection
    {
        return collect($this->strategies);
    }

    /**
     * Strategies the DB has marked active. Falls back to "all registered"
     * if nothing is configured yet — avoids a chicken-and-egg on fresh
     * installs.
     */
    public function active(): Collection
    {
        if (! \Schema::hasTable('trading_strategies')) {
            return $this->all();
        }

        $activeCodes = StrategyModel::query()->where('is_active', true)->pluck('code')->all();

        if (empty($activeCodes)) {
            return $this->all();
        }

        return $this->all()->only($activeCodes)->values();
    }

    public function parametersFor(string $code): array
    {
        $strategy = $this->get($code);
        if (! $strategy) {
            return [];
        }

        $defaults = $strategy->defaultParameters();
        $stored = StrategyModel::query()->where('code', $code)->value('parameters');

        return array_replace($defaults, is_array($stored) ? $stored : []);
    }
}
