<?php

namespace App\Jobs\Export;

use App\Exports\SaleMixedItemReportExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportSaleMixedItemReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public $filter, public $visibleColumns) {}

    public function handle()
    {
        $exportFileName = 'exports/SaleAndReturnItemReport_'.now()->timestamp.'.xlsx';
        Excel::store(new SaleMixedItemReportExport($this->filter, $this->visibleColumns), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('SaleMixedItemReport', $exportFileName));
    }
}
