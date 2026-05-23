<?php

namespace App\Console\Commands\Trading;

use App\Trading\Events\CircuitBreakerTripped;
use App\Trading\Risk\Rules\KillSwitchRule;
use Illuminate\Console\Command;

class KillSwitchCommand extends Command
{
    protected $signature = 'trade:kill-switch
        {action : engage|disengage|status}
        {--reason= : Free-text reason when engaging}';

    protected $description = 'Engage/disengage the trading kill switch.';

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'engage' => $this->engage(),
            'disengage' => $this->disengage(),
            'status' => $this->status(),
            default => $this->bail("Unknown action [{$action}]"),
        };
    }

    private function engage(): int
    {
        $reason = (string) ($this->option('reason') ?: 'manual');
        KillSwitchRule::engage($reason);
        CircuitBreakerTripped::dispatch("Kill switch engaged ({$reason})");
        $this->warn('🛑 Kill switch ENGAGED — all new BUY orders blocked.');

        return self::SUCCESS;
    }

    private function disengage(): int
    {
        KillSwitchRule::disengage();
        $this->info('✅ Kill switch DISENGAGED.');

        return self::SUCCESS;
    }

    private function status(): int
    {
        $this->info(KillSwitchRule::isEngaged() ? '🛑 Kill switch is ENGAGED' : '✅ Kill switch is OFF');

        return self::SUCCESS;
    }

    private function bail(string $msg): int
    {
        $this->error($msg);

        return self::FAILURE;
    }
}
