<?php

namespace App\Livewire\Trading;

use App\Models\TradingBacktestRun;
use App\Models\TradingBar;
use App\Trading\Backtest\BacktestEngine;
use App\Trading\DataObjects\Bar;
use App\Trading\Strategies\StrategyRegistry;
use Livewire\Component;

class BacktestRunner extends Component
{
    public string $strategyCode = 'momentum_score';

    public string $symbols = '';

    public string $message = '';

    public function run(StrategyRegistry $registry): void
    {
        $strategy = $registry->get($this->strategyCode);
        if (! $strategy) {
            $this->message = "Unknown strategy: {$this->strategyCode}";

            return;
        }

        $symbols = array_filter(array_map('trim', explode(',', $this->symbols)));
        if (empty($symbols)) {
            $this->message = 'Provide at least one symbol (comma-separated).';

            return;
        }

        $barsBySymbol = [];
        foreach ($symbols as $sym) {
            $rows = TradingBar::query()
                ->where('symbol', $sym)
                ->orderBy('ts')
                ->limit(2000)
                ->get();

            $barsBySymbol[$sym] = $rows->map(fn ($r) => new Bar(
                symbol: $r->symbol,
                timestamp: $r->ts->getTimestamp(),
                open: (float) $r->open,
                high: (float) $r->high,
                low: (float) $r->low,
                close: (float) $r->close,
                volume: (int) $r->volume,
                interval: $r->interval,
            ))->all();
        }

        $engine = new BacktestEngine();
        $result = $engine->run($strategy, $barsBySymbol);
        $row = $engine->persist($this->strategyCode, $result);
        $this->message = "Done. Run #{$row->id} — return {$result->totalReturnPercent}%, win-rate "
            .round($result->winRate * 100, 1).'%, '.count($result->trades).' trades.';
    }

    public function render()
    {
        return view('livewire.trading.backtest-runner', [
            'runs' => TradingBacktestRun::query()->latest()->limit(10)->get(),
        ]);
    }
}
