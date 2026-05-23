<?php

namespace App\Trading\Services;

use App\Models\TradingCommandRun;

/**
 * Tiny helper used by both trade:unified and trade:quick to write a
 * decision-audit row for every cron tick. Lets the dashboard show
 * "which tick fired, what it did, what it skipped and why".
 */
class CommandRunRecorder
{
    public function start(string $command, ?string $action = null): TradingCommandRun
    {
        return TradingCommandRun::create([
            'command' => $command,
            'action' => $action,
            'started_at' => now(),
            'outcome' => 'running',
        ]);
    }

    public function finish(TradingCommandRun $run, string $outcome, array $summary = [], ?string $error = null): void
    {
        $run->update([
            'finished_at' => now(),
            'outcome' => $outcome,
            'summary' => $summary,
            'error' => $error,
        ]);
    }
}
