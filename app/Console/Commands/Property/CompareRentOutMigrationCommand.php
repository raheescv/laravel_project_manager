<?php

namespace App\Console\Commands\Property;

use App\Actions\RentOut\Comparison\CompareRentOutPopulationAction;
use App\Actions\RentOut\Comparison\StoreRentOutComparisonAction;
use Illuminate\Console\Command;
use Throwable;

class CompareRentOutMigrationCommand extends Command
{
    protected $signature = 'property:compare-rentout-migration
        {--old-connection= : Legacy database connection; defaults to configured value}
        {--new-connection= : New project database connection; defaults to configured value}
        {--chunk=250 : Agreement query chunk size}
        {--id= : Compare one rent-out ID}
        {--ids= : Compare comma-separated rent-out IDs}
        {--type= : Limit comparison to rental or lease}
        {--fail-on-difference : Fail when a confirmed difference is found}';

    protected $description = 'Compare legacy and migrated rent-out records and store the live results';

    public function handle(
        CompareRentOutPopulationAction $compare,
        StoreRentOutComparisonAction $store,
    ): int {
        $type = $this->option('type') ?: null;
        if ($type !== null && ! in_array($type, ['rental', 'lease'], true)) {
            $this->error('The --type option must be rental or lease.');

            return self::INVALID;
        }

        $ids = collect(explode(',', (string) $this->option('ids')))
            ->push($this->option('id'))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn ($id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize < 1) {
            $this->error('The --chunk option must be at least 1.');

            return self::INVALID;
        }

        $this->components->info('Comparing legacy and migrated rent-out records');

        try {
            $comparison = $compare->execute(
                oldConnection: (string) ($this->option('old-connection') ?: config('rentout-comparison.old_connection')),
                newConnection: (string) ($this->option('new-connection') ?: config('rentout-comparison.new_connection')),
                ids: $ids,
                type: $type,
                chunkSize: $chunkSize,
                progress: fn (int $done, int $total) => $this->output->write("\rCompared {$done} / {$total} records"),
            );
            $this->newLine();
            $stored = $store->execute($comparison);
        } catch (Throwable $throwable) {
            $this->newLine();
            $this->components->error($throwable->getMessage());

            return self::FAILURE;
        }

        $summary = $comparison['summary'];
        $this->table(
            ['Compared', 'Matching', 'Differing', 'Missing', 'Extra', 'Match rate'],
            [[
                $summary['total'],
                $summary['matching'],
                $summary['differing'],
                $summary['missing'],
                $summary['extra'],
                $summary['match_percentage'].'%',
            ]],
        );
        $this->line("Stored: {$stored['stored']} rows");
        $this->line("Removed stale rows: {$stored['deleted']}");
        $this->line('Live report: '.route('property::rentout-comparison'));

        if ($this->option('fail-on-difference') && $summary['differing'] > 0) {
            $this->components->warn('Comparison completed, but differences were found.');

            return self::FAILURE;
        }

        $this->components->info('Live comparison data refreshed successfully.');

        return self::SUCCESS;
    }
}
