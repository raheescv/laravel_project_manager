<?php

namespace App\Console\Commands\Trading;

use App\Trading\Pipelines\EntryPipeline;
use App\Trading\Pipelines\ExitPipeline;
use App\Trading\Pipelines\SquareoffPipeline;
use App\Trading\Risk\Rules\KillSwitchRule;
use App\Trading\Services\CommandRunRecorder;
use App\Trading\Sizing\PositionSizer;
use App\Trading\Strategies\StrategyRegistry;
use App\Trading\Universe\UniverseSelector;
use Illuminate\Console\Command;

/**
 * High-cadence (every ~2 min) variant of trade:unified that combines a
 * sell-first / buy-best cycle into one tick. Keeps concurrent positions
 * tight (default 1) and uses the same pipeline stack — no separate
 * "intelligent" logic. The frequency is the strategy.
 */
class QuickTradingCommand extends Command
{
    protected $signature = 'trade:quick
                            {--mode=paper : live | paper | dry}
                            {--strategy=momentum_score : strategy code from StrategyRegistry}
                            {--universe=nifty50 : nifty50 | active | custom:SYM1,SYM2}
                            {--risk-per-trade=0.005 : Fractional account risk per trade (tighter than unified)}
                            {--funds-buffer=0.05 : Fractional cash headroom kept for fees/STT/slippage}
                            {--max-concurrent=1 : Cap on concurrent positions}
                            {--max-position-size=25000 : Notional cap per order}
                            {--squareoff-at=15:20 : HH:MM time exit}
                            {--bar-interval=5m : Bar timeframe}
                            {--lookback-bars=120 : Bars of history}
                            {--sell-all : Hard flatten all positions (used by end-of-window cron)}';

    protected $description = 'Quick cycle — exit-first then best-single-entry, or hard flatten with --sell-all';

    public function handle(
        CommandRunRecorder $recorder,
        StrategyRegistry $registry,
        UniverseSelector $universe,
        ExitPipeline $exit,
        SquareoffPipeline $squareoff,
    ): int {
        $sellAll = (bool) $this->option('sell-all');
        $mode = $this->option('mode');
        $action = $sellAll ? 'squareoff' : 'quick';

        $run = $recorder->start('trade:quick', $action);
        $this->line("trade:quick action={$action} mode={$mode}");

        try {
            if ($sellAll) {
                $summary = $squareoff->run($this->pipelineOptions($mode));
            } else {
                $exitResult = $exit->run($this->pipelineOptions($mode));
                $entryResult = KillSwitchRule::isEngaged()
                    ? ['status' => 'skipped_kill_switch', 'placed' => []]
                    : $this->runEntry($registry, $universe, $mode);

                $summary = [
                    'status' => 'ok',
                    'exited' => $exitResult['exited'] ?? [],
                    'placed' => $entryResult['placed'] ?? [],
                    'entry_status' => $entryResult['status'] ?? null,
                ];
            }

            $recorder->finish($run, 'success', $summary);
            $this->renderSummary($summary);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $recorder->finish($run, 'error', [], $e->getMessage());
            $this->error('quick tick failed: '.$e->getMessage());
            \Log::error('trade:quick failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    private function runEntry(StrategyRegistry $registry, UniverseSelector $universe, string $mode): array
    {
        $code = $this->option('strategy');
        $strategy = $registry->get($code) ?? throw new \InvalidArgumentException("unknown strategy: {$code}");
        $symbols = $universe->resolve($this->option('universe'));
        if (empty($symbols)) {
            return ['status' => 'skipped_empty_universe', 'placed' => []];
        }

        app()->instance(PositionSizer::class, new PositionSizer(
            riskPerTrade: (float) $this->option('risk-per-trade'),
            maxNotional: (float) $this->option('max-position-size'),
            fundsBuffer: (float) $this->option('funds-buffer'),
        ));

        return app(EntryPipeline::class)->run($strategy, $symbols, $this->pipelineOptions($mode) + [
            'parameters' => $registry->parametersFor($code),
            'strategy_code' => $code,
        ]);
    }

    private function pipelineOptions(string $mode): array
    {
        return [
            'mode' => $mode,
            'max_concurrent' => (int) $this->option('max-concurrent'),
            'bar_interval' => $this->option('bar-interval'),
            'lookback_bars' => (int) $this->option('lookback-bars'),
            'squareoff_at' => $this->option('squareoff-at'),
        ];
    }

    private function renderSummary(array $summary): void
    {
        if (! empty($summary['exited'])) {
            $this->line('exited:');
            foreach ($summary['exited'] as $row) {
                $this->line("  {$row['symbol']} qty={$row['quantity']} kind=".($row['kind'] ?? '?'));
            }
        }
        if (! empty($summary['placed'])) {
            $this->line('placed:');
            foreach ($summary['placed'] as $row) {
                $this->line("  {$row['symbol']} qty={$row['quantity']}");
            }
        }
        if (! empty($summary['flattened'])) {
            $this->line('flattened:');
            foreach ($summary['flattened'] as $row) {
                $this->line("  {$row['symbol']} qty={$row['quantity']}");
            }
        }
        if (empty($summary['exited']) && empty($summary['placed']) && empty($summary['flattened'])) {
            $this->line('nothing to do');
        }
    }
}
