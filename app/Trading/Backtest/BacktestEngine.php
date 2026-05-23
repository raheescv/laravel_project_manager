<?php

namespace App\Trading\Backtest;

use App\Models\TradingBacktestRun;
use App\Trading\Contracts\Strategy;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\Signal;
use App\Trading\Support\Indicators;

/**
 * Event-driven backtester.
 *
 * Replays bars to the SAME Strategy classes that run live. The engine
 * keeps a simple long-only portfolio: one open position per symbol, sized
 * by capital_per_trade. Slippage + commission are configurable.
 *
 * Limitations (documented on purpose so the UI can warn):
 *   - long-only
 *   - one position per symbol
 *   - fills at next bar's open (no intra-bar simulation)
 */
class BacktestEngine
{
    public function __construct(
        public readonly float $initialCapital = 100_000.0,
        public readonly float $slippageBps = 5.0,
        public readonly float $commissionPerOrder = 20.0,
        public readonly float $capitalPerTrade = 10_000.0,
    ) {}

    /**
     * Run a backtest.
     *
     * @param  array<string, Bar[]>  $barsBySymbol  chronological asc per symbol
     */
    public function run(Strategy $strategy, array $barsBySymbol, array $parameters = []): BacktestResult
    {
        $cash = $this->initialCapital;
        $positions = []; // symbol => ['qty','entry','entry_idx','stop','target']
        $trades = [];
        $equityCurve = [];

        // Build a global time axis from the union of bar timestamps.
        $timeAxis = collect($barsBySymbol)
            ->flatMap(fn ($bars) => array_map(fn (Bar $b) => $b->timestamp, $bars))
            ->unique()->sort()->values()->all();

        foreach ($timeAxis as $i => $ts) {
            $portfolioValue = $cash;

            foreach ($barsBySymbol as $symbol => $bars) {
                $idx = $this->indexAt($bars, $ts);
                if ($idx === null) {
                    continue;
                }
                $bar = $bars[$idx];
                $window = array_slice($bars, 0, $idx + 1);

                // Mark to market.
                if (isset($positions[$symbol])) {
                    $portfolioValue += $positions[$symbol]['qty'] * $bar->close;

                    // Check stop/target intrabar (high/low).
                    $pos = $positions[$symbol];
                    if ($bar->low <= $pos['stop']) {
                        $exit = $pos['stop'] * (1 - $this->slippageBps / 10_000);
                        $trades[] = $this->closeTrade($symbol, $pos, $exit, $ts, 'stop');
                        $cash += $pos['qty'] * $exit - $this->commissionPerOrder;
                        unset($positions[$symbol]);

                        continue;
                    }
                    if ($bar->high >= $pos['target']) {
                        $exit = $pos['target'] * (1 - $this->slippageBps / 10_000);
                        $trades[] = $this->closeTrade($symbol, $pos, $exit, $ts, 'target');
                        $cash += $pos['qty'] * $exit - $this->commissionPerOrder;
                        unset($positions[$symbol]);

                        continue;
                    }
                }

                // Ask the strategy.
                $signal = $strategy->score($symbol, $window, ['parameters' => $parameters]);

                if ($signal->action === Signal::ACTION_BUY && ! isset($positions[$symbol]) && $cash > $this->capitalPerTrade) {
                    $entry = $bar->close * (1 + $this->slippageBps / 10_000);
                    $qty = (int) floor($this->capitalPerTrade / max($entry, 0.01));
                    if ($qty < 1) {
                        continue;
                    }
                    $cash -= $qty * $entry + $this->commissionPerOrder;
                    $positions[$symbol] = [
                        'qty' => $qty,
                        'entry' => $entry,
                        'entry_ts' => $ts,
                        'stop' => $signal->stopLoss ?? $entry * 0.97,
                        'target' => $signal->target ?? $entry * 1.05,
                    ];
                } elseif ($signal->action === Signal::ACTION_SELL && isset($positions[$symbol])) {
                    $exit = $bar->close * (1 - $this->slippageBps / 10_000);
                    $trades[] = $this->closeTrade($symbol, $positions[$symbol], $exit, $ts, 'signal');
                    $cash += $positions[$symbol]['qty'] * $exit - $this->commissionPerOrder;
                    unset($positions[$symbol]);
                }
            }

            $equityCurve[] = ['ts' => $ts, 'equity' => $portfolioValue];
        }

        $finalEquity = end($equityCurve)['equity'] ?? $cash;
        $returns = $this->dailyReturns($equityCurve);
        $totalReturn = (($finalEquity - $this->initialCapital) / $this->initialCapital) * 100;
        $wins = array_filter($trades, fn ($t) => $t['pnl'] > 0);

        return new BacktestResult(
            initialCapital: $this->initialCapital,
            finalEquity: (float) $finalEquity,
            totalReturnPercent: (float) $totalReturn,
            maxDrawdownPercent: Indicators::maxDrawdown(array_column($equityCurve, 'equity')),
            sharpe: Indicators::sharpe($returns),
            sortino: Indicators::sortino($returns),
            winRate: count($trades) ? count($wins) / count($trades) : 0.0,
            trades: $trades,
            equityCurve: $equityCurve,
        );
    }

    public function persist(string $strategyCode, BacktestResult $r, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null, array $parameters = []): TradingBacktestRun
    {
        return TradingBacktestRun::create([
            'strategy_code' => $strategyCode,
            'from_date' => $from ?? now()->subYear(),
            'to_date' => $to ?? now(),
            'initial_capital' => $r->initialCapital,
            'final_equity' => $r->finalEquity,
            'total_return_percent' => $r->totalReturnPercent,
            'max_drawdown_percent' => $r->maxDrawdownPercent,
            'sharpe' => $r->sharpe,
            'sortino' => $r->sortino,
            'win_rate' => $r->winRate,
            'trades_count' => count($r->trades),
            'parameters' => $parameters,
            'equity_curve' => $r->equityCurve,
            'trades' => $r->trades,
            'status' => 'done',
        ]);
    }

    private function indexAt(array $bars, int $ts): ?int
    {
        foreach ($bars as $i => $bar) {
            if ($bar->timestamp === $ts) {
                return $i;
            }
        }

        return null;
    }

    private function closeTrade(string $symbol, array $pos, float $exit, int $ts, string $reason): array
    {
        $pnl = ($exit - $pos['entry']) * $pos['qty'];

        return [
            'symbol' => $symbol,
            'qty' => $pos['qty'],
            'entry' => $pos['entry'],
            'exit' => $exit,
            'entry_ts' => $pos['entry_ts'],
            'exit_ts' => $ts,
            'pnl' => $pnl,
            'pnl_pct' => ($exit - $pos['entry']) / max($pos['entry'], 0.0001) * 100,
            'reason' => $reason,
        ];
    }

    private function dailyReturns(array $curve): array
    {
        $returns = [];
        for ($i = 1; $i < count($curve); $i++) {
            $prev = $curve[$i - 1]['equity'];
            if ($prev > 0) {
                $returns[] = ($curve[$i]['equity'] - $prev) / $prev;
            }
        }

        return $returns;
    }
}
