<?php

namespace App\Jobs;

use App\Models\Visitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackVisitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $maxExceptions = 3;

    public $timeout = 30;

    public function __construct(private array $visitorData) {}

    public function handle(): void
    {
        $cacheKey = 'visitor_batch_'.date('Y-m-d-H');
        $batchSize = 10;
        $minimumTimeRemaining = 300; // 5 minutes minimum time remaining

        // Add to current batch
        $batch = Cache::get($cacheKey);
        if (! $batch) {
            $batch = [
                'data' => [],
                'created_at' => now()->timestamp,
            ];
        }
        $batch['data'][] = $this->visitorData;

        // Check if we should force insert based on elapsed time or batch size
        $elapsedTime = now()->timestamp - $batch['created_at'];
        $timeRemaining = 3600 - $elapsedTime;

        if (count($batch['data']) >= $batchSize || $timeRemaining <= $minimumTimeRemaining) {
            Visitor::insert($batch['data']);
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $batch, 3600);
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure and ensure single record is inserted
        Log::error('Visitor tracking failed: '.$exception->getMessage());
        Visitor::create($this->visitorData);
    }
}
