<?php

namespace App\Jobs\Export;

use App\Exports\UserExport;
use App\Jobs\Middleware\RunInTenant;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    protected $filters;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RunInTenant($this->user->tenant_id)];
    }

    public function handle()
    {
        $exportFileName = 'exports/User_'.now()->timestamp.'.xlsx';
        Excel::store(new UserExport($this->filters), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('User', $exportFileName));
    }
}
