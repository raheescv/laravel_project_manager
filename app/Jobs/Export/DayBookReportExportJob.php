<?php

namespace App\Jobs\Export;

use App\Exports\DayBookReportExport;
use App\Jobs\Middleware\RunInTenant;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class DayBookReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public $filter) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RunInTenant($this->user->tenant_id)];
    }

    public function handle()
    {
        $exportFileName = 'exports/DayBookReport_'.now()->timestamp.'.xlsx';
        Excel::store(new DayBookReportExport($this->filter), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('DayBookReport', $exportFileName));
    }
}
