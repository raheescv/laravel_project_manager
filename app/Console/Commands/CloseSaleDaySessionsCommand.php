<?php

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Models\SaleDaySession;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseSaleDaySessionsCommand extends Command
{
    protected $signature = 'sale-day-sessions:close-daily';

    protected $description = 'Close all open sale day sessions across all branches, setting closing amount to expected amount';

    public function handle()
    {
        // Check if auto-close is enabled
        $autoCloseEnabled = Configuration::where('key', 'auto_close_day_sessions_enabled')->value('value');

        if ($autoCloseEnabled !== 'yes') {
            $this->info('Auto-close day sessions feature is disabled. Exiting...');

            return 0;
        }

        $this->info('Starting to close open sale day sessions...');

        // Get all open sessions without global scopes to include all branches
        $openSessions = SaleDaySession::withoutGlobalScopes()->open()->with('sales')->get();

        if ($openSessions->isEmpty()) {
            $this->info('No open sessions found.');

            return 0;
        }

        $this->info("Found {$openSessions->count()} open session(s) to close.");

        $closedCount = 0;
        $failedCount = 0;

        foreach ($openSessions as $session) {
            try {
                DB::beginTransaction();

                // Calculate expected amount: opening_amount + total sales (matching close() method logic)
                // The sales() relationship already filters for completed sales
                $totalSalesAmount = $session->sales->sum('paid');
                $expectedAmount = $session->opening_amount + $totalSalesAmount;

                // Close the session with closing_amount = expected_amount
                $session->close(
                    $expectedAmount, // closing_amount = expected_amount
                    0, // sync_amount (set to 0 for auto-close)
                    1, // closed_by (null for system command)
                    'Auto-closed by daily scheduled command', // notes
                );

                DB::commit();

                $closedCount++;
                $this->info("✓ Closed session ID {$session->id} for branch ID {$session->branch_id} (Expected: {$expectedAmount})");
            } catch (Exception $e) {
                DB::rollBack();
                $failedCount++;
                $this->error("✗ Failed to close session ID {$session->id} for branch ID {$session->branch_id}: {$e->getMessage()}");
            }
        }

        $this->info("\nSummary:");
        $this->info("  Closed: {$closedCount}");
        $this->info("  Failed: {$failedCount}");

        return $failedCount > 0 ? 1 : 0;
    }
}
