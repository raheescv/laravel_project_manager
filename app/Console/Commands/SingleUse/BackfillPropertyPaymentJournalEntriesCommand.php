<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\RentOut\BackfillPropertyPaymentJournalEntriesAction;
use Illuminate\Console\Command;

class BackfillPropertyPaymentJournalEntriesCommand extends Command
{
    protected $signature = 'app:backfill-property-payment-journal-entries {--tenant= : Limit to a specific tenant ID} {--dry-run : Preview without writing records}';

    protected $description = 'Backfill missing property payment transactions and journal entries from already-paid rent and utility terms';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant') !== null ? (int) $this->option('tenant') : null;

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No transactions or journals will be created.');
        }

        $result = (new BackfillPropertyPaymentJournalEntriesAction())->execute($tenantId, $dryRun, $this);

        $this->newLine();
        $this->info("Backfill complete. Rent terms created: {$result['rent_terms_created']}. Utility terms created: {$result['utility_terms_created']}.");

        return self::SUCCESS;
    }
}
