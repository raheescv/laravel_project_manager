<?php

namespace App\Jobs\Export;

use App\Exports\ExpenseExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportExpenseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        $exportFileName = 'exports/expense_'.now()->timestamp.'.xlsx';
        Excel::store(new ExpenseExport(), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Expense', $exportFileName));
    }
}
