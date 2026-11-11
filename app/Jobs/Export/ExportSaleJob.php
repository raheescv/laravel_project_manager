<?php

namespace App\Jobs\Export;

use App\Exports\SaleExport;
use App\Jobs\Middleware\RunInTenant;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $filters  the sale list's filters, as passed to Sale::filter()
     */
    public function __construct(protected User $user, protected array $filters = []) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RunInTenant($this->user->tenant_id)];
    }

    public function handle()
    {
        $exportFileName = 'exports/Sale_'.now()->timestamp.'.xlsx';
        Excel::store(new SaleExport($this->filters), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Sale', $exportFileName));
    }
}
