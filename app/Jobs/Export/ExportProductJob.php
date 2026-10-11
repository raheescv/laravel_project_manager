<?php

namespace App\Jobs\Export;

use App\Exports\ProductExport;
use App\Jobs\Middleware\RunInTenant;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected User $user, public $filter) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RunInTenant($this->user->tenant_id)];
    }

    public function handle()
    {
        $exportFileName = 'exports/Product_'.now()->timestamp.'.xlsx';
        Excel::store(new ProductExport($this->filter), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Product', $exportFileName));
    }
}
