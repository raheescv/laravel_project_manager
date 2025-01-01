<?php

namespace App\Listeners;

use App\Events\FileImportCompleted;
use App\Exports\ErrorsExport;
use App\Models\User;
use App\Notifications\ImportErrorsNotification;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class SendImportErrorsNotification
{
    public function handle(FileImportCompleted $event)
    {
        $user = User::find($event->userId);

        $file_path = 'exports/product_errors-' . now()->timestamp . '.xlsx';
        Excel::store(new ErrorsExport($event->errors), $file_path, 'public');

        Notification::send($user, new ImportErrorsNotification($event->entity, $file_path, $event->errors));
    }
}
