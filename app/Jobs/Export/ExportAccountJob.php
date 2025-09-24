<?php

namespace App\Jobs\Export;

use App\Exports\AccountExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filter;
    protected $user;

    public function __construct(User $user,$filter = [])
    {
        $this->user = $user;
        $this->filter = $filter;
    }

    public function handle()
    {
        $exportFileName = 'exports/account_'.now()->timestamp.'.xlsx';
        Excel::store(new AccountExport($this->filter), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Account', $exportFileName));
    }
}
