<?php

namespace App\Console\Commands\SingleUse;

use App\Models\InventoryLog;
use Illuminate\Console\Command;

class UpdateInventoryLogsDateCommand extends Command
{
    protected $signature = 'inventory:update-logs-date';

    protected $description = 'Update inventory logs created_at based on their associated model date';

    public function handle()
    {
        $query = InventoryLog::query()->whereNotNull('model')->whereNotNull('model_id');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No inventory logs found to update.');

            return;
        }

        $this->info("Found {$count} logs to process");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunk(100, function ($logs) use ($bar): void {
            foreach ($logs as $log) {
                try {
                    // Get the creation date of the associated model
                    $modelClass = 'App\\Models\\'.$log->model;
                    if (class_exists($modelClass)) {
                        if ($log->model != 'InventoryTransfer') {
                            $model = $modelClass::withTrashed()->find($log->model_id);
                        } else {
                            $model = $modelClass::find($log->model_id);
                        }
                        if ($model && $model->date) {
                            $log->created_at = date('Y-m-d H:i:s', strtotime($model->date));
                            $log->updated_at = date('Y-m-d H:i:s', strtotime($model->date));
                            $log->save();
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing log ID {$log->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Inventory logs dates have been updated successfully!');
    }
}
