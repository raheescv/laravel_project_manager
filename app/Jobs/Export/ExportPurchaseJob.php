<?php

namespace App\Jobs\Export;

use App\Exports\PurchaseExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        $exportFileName = 'exports/Purchase_'.now()->timestamp.'.xlsx';
        Excel::store(new PurchaseExport, $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Purchase', $exportFileName));
    }
}
