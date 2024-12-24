<?php

namespace App\Jobs\Category;

use App\Imports\CategoryImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ImportCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected $user_id, protected $filePath) {}

    public function handle()
    {
        $file = storage_path('app/public/'.$this->filePath);
        $totalRows = Excel::toCollection(null, $file)->first()->count() - 1;
        Excel::import(new CategoryImport($this->user_id, $totalRows), $file);
        unlink($file);
    }
}
