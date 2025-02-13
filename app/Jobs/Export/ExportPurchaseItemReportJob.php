<?php

namespace App\Jobs\Export;

use App\Exports\PurchaseItemReportExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseItemReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public $filter) {}

    public function handle()
    {
        $exportFileName = 'exports/PurchaseItemReport_'.now()->timestamp.'.xlsx';
        Excel::store(new PurchaseItemReportExport($this->filter), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('PurchaseItemReport', $exportFileName));
    }
}
