<?php

namespace App\Trading\Backtest;

final class BacktestResult
{
    public function __construct(
        public readonly float $initialCapital,
        public readonly float $finalEquity,
        public readonly float $totalReturnPercent,
        public readonly float $maxDrawdownPercent,
        public readonly float $sharpe,
        public readonly float $sortino,
        public readonly float $winRate,
        public readonly array $trades,
        public readonly array $equityCurve,
    ) {}

    public function toArray(): array
    {
        return [
            'initial_capital' => $this->initialCapital,
            'final_equity' => $this->finalEquity,
            'total_return_percent' => $this->totalReturnPercent,
            'max_drawdown_percent' => $this->maxDrawdownPercent,
            'sharpe' => $this->sharpe,
            'sortino' => $this->sortino,
            'win_rate' => $this->winRate,
            'trades_count' => count($this->trades),
            'trades' => $this->trades,
            'equity_curve' => $this->equityCurve,
        ];
    }
}
