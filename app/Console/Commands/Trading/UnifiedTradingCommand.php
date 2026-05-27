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

class UnifiedTradingCommand extends Command
{
    protected $signature = 'trade:unified
                            {--action=buy : buy | sell | squareoff}
                            {--mode=paper : live | paper | dry}
                            {--strategy=momentum_score : strategy code from StrategyRegistry}
                            {--universe=nifty50 : nifty50 | active | custom:SYM1,SYM2}
                            {--risk-per-trade=0.01 : Fractional account risk per trade}
                            {--max-concurrent=3 : Safety cap on concurrent positions}
                            {--max-position-size=50000 : Notional cap per order}
                            {--squareoff-at=15:20 : HH:MM time exit for intraday}
                            {--bar-interval=5m : Bar timeframe to score on}
                            {--lookback-bars=120 : Bars of history to fetch}';

    protected $description = 'Run one tick of the trading pipeline (entry / exit / squareoff)';

    public function handle(
        CommandRunRecorder $recorder,
        StrategyRegistry $registry,
        UniverseSelector $universe,
        EntryPipeline $entry,
        ExitPipeline $exit,
        SquareoffPipeline $squareoff,
    ): int {
        $action = $this->option('action');
        $mode = $this->option('mode');

        $run = $recorder->start('trade:unified', $action);
        $this->line("trade:unified action={$action} mode={$mode}");

        if ($action === 'buy' && KillSwitchRule::isEngaged()) {
            $recorder->finish($run, 'skipped', ['reason' => 'kill_switch']);
            $this->warn('kill switch engaged — skipping buy tick');

            return self::SUCCESS;
        }

        try {
            $summary = match ($action) {
                'buy' => $this->runEntry($entry, $registry, $universe, $mode),
                'sell' => $exit->run($this->pipelineOptions($mode)),
                'squareoff' => $squareoff->run($this->pipelineOptions($mode)),
                default => throw new \InvalidArgumentException("unknown --action={$action}"),
            };

            $recorder->finish($run, 'success', $summary);
            $this->renderSummary($action, $summary);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $recorder->finish($run, 'error', [], $e->getMessage());
            $this->error('tick failed: '.$e->getMessage());
            \Log::error('trade:unified failed', ['error' => $e->getMessage(), 'action' => $action]);

            return self::FAILURE;
        }
    }

    private function runEntry(EntryPipeline $entry, StrategyRegistry $registry, UniverseSelector $universe, string $mode): array
    {
        $code = $this->option('strategy');
        $strategy = $registry->get($code) ?? throw new \InvalidArgumentException("unknown strategy: {$code}");

        $symbols = $universe->resolve($this->option('universe'));
        if (empty($symbols)) {
            return ['status' => 'skipped_empty_universe'];
        }

        // The PositionSizer is constructed with command-line risk params so a
        // single binding can serve every per-tick invocation with the right
        // numbers without reaching into config from inside the pipeline.
        app()->instance(PositionSizer::class, new PositionSizer(
            riskPerTrade: (float) $this->option('risk-per-trade'),
            maxNotional: (float) $this->option('max-position-size'),
        ));

        $entry = app(EntryPipeline::class);

        return $entry->run($strategy, $symbols, $this->pipelineOptions($mode) + [
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

    private function renderSummary(string $action, array $summary): void
    {
        $status = $summary['status'] ?? 'unknown';
        if ($status !== 'ok') {
            $this->warn("status={$status}".(isset($summary['reason']) ? "  reason={$summary['reason']}" : ''));

            return;
        }

        if (isset($summary['regime'])) {
            $this->line('regime: '.$summary['regime']);
        }
        if (isset($summary['equity'])) {
            $this->line('equity: ₹'.number_format((float) $summary['equity'], 2));
        }
        if (isset($summary['max_concurrent'])) {
            $held = $summary['held_at_start'] ?? 0;
            $this->line("slots: held={$held} max_concurrent={$summary['max_concurrent']}");
        }

        $placedKey = match ($action) {
            'buy' => 'placed',
            'sell' => 'exited',
            'squareoff' => 'flattened',
            default => null,
        };
        $placed = $placedKey && isset($summary[$placedKey]) ? $summary[$placedKey] : [];

        if (! empty($placed)) {
            $this->line("\n{$placedKey}:");
            $this->table(
                ['symbol', 'qty', 'entry/exit', 'extra'],
                collect($placed)->map(fn ($r) => [
                    $r['symbol'] ?? '?',
                    $r['quantity'] ?? 0,
                    isset($r['entry']) ? '₹'.number_format($r['entry'], 2) : '—',
                    $r['kind'] ?? ($r['mode'] ?? (isset($r['rr']) ? 'rr='.$r['rr'] : 'live')),
                ])->all()
            );
        } else {
            $this->line("{$action}: 0 orders");
        }

        $skipped = $summary['skipped'] ?? [];
        if (! empty($skipped)) {
            $reasons = collect($skipped)
                ->countBy('reason')
                ->sortDesc()
                ->map(fn ($n, $r) => "{$r}={$n}")
                ->implode('  ');
            $this->line("\nskipped ({$reasons})");

            $sample = collect($skipped)->take(10)->map(fn ($s) => [
                $s['symbol'] ?? '?',
                $s['reason'] ?? '?',
                collect($s)->except(['symbol', 'reason'])
                    ->map(fn ($v, $k) => is_scalar($v) ? "{$k}={$v}" : "{$k}=…")
                    ->implode(' '),
            ])->all();
            $this->table(['symbol', 'reason', 'detail'], $sample);
        }
    }
}
