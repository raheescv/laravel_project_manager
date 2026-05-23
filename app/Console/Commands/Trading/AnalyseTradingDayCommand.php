<?php

namespace App\Console\Commands\Trading;

use App\Trading\Ai\TradeAnalyst;
use Illuminate\Console\Command;

class AnalyseTradingDayCommand extends Command
{
    protected $signature = 'trade:analyse {--date= : YYYY-MM-DD (defaults to today)}';

    protected $description = 'Generate an AI post-mortem of the day\'s trading activity.';

    public function handle(TradeAnalyst $analyst): int
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();

        $this->info('🤖 Generating AI post-mortem for '.$date->toDateString().'...');
        $analysis = $analyst->dailyPostmortem($date);

        if (! $analysis) {
            $this->warn('No trading activity found for that date.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->line($analysis->response);

        return self::SUCCESS;
    }
}
