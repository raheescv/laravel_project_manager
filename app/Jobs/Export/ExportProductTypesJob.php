<?php

namespace App\Jobs\Export;

use App\Exports\ProductTypesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use App\Notifications\ExportCompleted;
use App\Exports\ProductTypeExport;

class ExportProductTypesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        $exportFileName = 'exports/product_types_' . now()->timestamp . '.xlsx';
        Excel::store(new ProductTypeExport, $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('ProductType', $exportFileName));
    }
}
