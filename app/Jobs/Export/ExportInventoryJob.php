<?php

namespace App\Jobs\Export;

use App\Exports\InventoryExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExportInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public $tries = 3;

    public function __construct(protected User $user, public array $filters) {}

    public function handle()
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $exportFileName = "exports/inventory_{$timestamp}.xlsx";

            Excel::store(new InventoryExport($this->filters), $exportFileName, 'public');

            $this->user->notify(new ExportCompleted('Inventory', $exportFileName));

            Log::info('Inventory export completed successfully', [
                'user_id' => $this->user->id,
                'filename' => $exportFileName,
                'filters' => $this->filters,
            ]);

        } catch (\Exception $e) {
            Log::error('Inventory export failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'filters' => $this->filters,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Inventory export job failed', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
            'filters' => $this->filters,
        ]);
    }
}
