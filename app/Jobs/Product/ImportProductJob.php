<?php

namespace App\Jobs\Product;

use App\Imports\ProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected $user_id, protected $filePath) {}

    public function handle()
    {
        $file = storage_path('app/public/'.$this->filePath);
        $collection = Excel::toCollection(null, $file)->first();
        $totalRows = $collection->filter(function ($row) {
            return $row->filter()->isNotEmpty();
        })->count()-1;
        Excel::import(new ProductImport($this->user_id, $totalRows), $file);
        unlink($file);
    }
}
