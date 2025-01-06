<?php

namespace App\Jobs\Export;

use App\Exports\SaleExport;
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

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        $exportFileName = 'exports/Sale_'.now()->timestamp.'.xlsx';
        Excel::store(new SaleExport, $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Sale', $exportFileName));
    }
}
