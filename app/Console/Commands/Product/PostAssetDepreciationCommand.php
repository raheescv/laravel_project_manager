<?php

namespace App\Console\Commands\Product;

use App\Actions\Asset\PostDueDepreciationAction;
use Illuminate\Console\Command;

class PostAssetDepreciationCommand extends Command
{
    protected $signature = 'assets:post-depreciation {--date=} {--branch_id=} {--asset_id=}';

    protected $description = 'Post due asset depreciation schedules to journals';

    public function handle(): int
    {
        $response = (new PostDueDepreciationAction())->execute(
            1,
            $this->option('date') ?: now()->toDateString(),
            $this->option('branch_id') ? (int) $this->option('branch_id') : null,
            $this->option('asset_id') ? (int) $this->option('asset_id') : null,
        );

        $this->info($response['message']);
        foreach (($response['data']['failed'] ?? []) as $failure) {
            $this->warn(($failure['asset'] ?: 'Unknown Asset').' ['.$failure['schedule_date'].']: '.$failure['message']);
        }

        return self::SUCCESS;
    }
}
