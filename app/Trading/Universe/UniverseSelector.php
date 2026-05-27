<?php

namespace App\Trading\Universe;

use App\Models\TradingStrategy;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a --universe= argument into a list of tradable symbols.
 *
 *   nifty50              static Nifty 50 list
 *   active               symbols configured on the currently active strategies
 *   custom:SYM1,SYM2     explicit list
 */
final class UniverseSelector
{
    public function resolve(string $spec): array
    {
        if (str_starts_with($spec, 'custom:')) {
            return $this->parseCustom(substr($spec, 7));
        }

        return match ($spec) {
            'active' => $this->fromActiveStrategies(),
            'nifty50' => $this->nifty50(),
            default => $this->nifty50(),
        };
    }

    public function nifty50(): array
    {
        return Cache::remember('trading:universe:nifty50', 3600, fn () => [
            'RELIANCE', 'TCS', 'HDFCBANK', 'INFY', 'HINDUNILVR', 'ITC', 'SBIN', 'BHARTIARTL',
            'KOTAKBANK', 'LT', 'ASIANPAINT', 'AXISBANK', 'MARUTI', 'SUNPHARMA', 'TITAN', 'ULTRACEMCO',
            'WIPRO', 'NESTLEIND', 'ONGC', 'POWERGRID', 'NTPC', 'TECHM', 'TATAMOTORS', 'BAJFINANCE',
            'HCLTECH', 'BAJAJFINSV', 'DRREDDY', 'JSWSTEEL', 'TATASTEEL', 'COALINDIA', 'GRASIM', 'BRITANNIA',
            'EICHERMOT', 'HEROMOTOCO', 'DIVISLAB', 'CIPLA', 'APOLLOHOSP', 'ADANIPORTS', 'INDUSINDBK', 'TATACONSUM',
            'BPCL', 'ICICIBANK', 'ADANIENT', 'HDFCLIFE', 'SBILIFE', 'BAJAJ-AUTO', 'UPL', 'SHREECEM',
        ]);
    }

    private function parseCustom(string $csv): array
    {
        return collect(explode(',', $csv))
            ->map(fn ($s) => strtoupper(trim($s)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function fromActiveStrategies(): array
    {
        if (! \Schema::hasTable('trading_strategies')) {
            return $this->nifty50();
        }

        $symbols = TradingStrategy::query()
            ->where('is_active', true)
            ->pluck('parameters')
            ->flatMap(fn ($p) => is_array($p) ? ($p['symbols'] ?? []) : [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($symbols) ? $this->nifty50() : $symbols;
    }
}
