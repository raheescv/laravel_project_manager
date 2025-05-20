<?php

namespace App\Console\Commands;

use App\Models\Visitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProcessVisitorBatchesCommand extends Command
{
    protected $signature = 'visitors:process-batches';

    protected $description = 'Process any remaining visitor batches in cache';

    public function handle(): void
    {
        $key = date('Y-m-d-H');
        $batch = Cache::get('visitor_batch_'.$key);
        if ($batch) {
            $batch = $batch['data'];
            if ($batch && count($batch) > 0) {
                $this->info('Processing batch '.$key.' with '.count($batch).' records');
                Visitor::insert($batch);
                Cache::forget('visitor_batch_'.$key);
            }
        } else {
            $this->info('No batch found for key '.$key);
        }
    }
}
