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

    public function __construct(protected $user_id, protected $filePath, protected $branchId = null) {}

    public function handle()
    {
        $file = storage_path('app/public/'.$this->filePath);
        $collection = Excel::toCollection(null, $file)->first();
        $totalRows = $collection->filter(function ($row) {
            return $row->filter()->isNotEmpty();
        })->count();

        $totalRows--;
        Excel::import(new ProductImport($this->user_id, $totalRows, $this->branchId), $file);
        unlink($file);
    }
}
