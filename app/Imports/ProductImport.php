<?php

namespace App\Imports;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $processedRows = 0;

    private $errors = [];

    public function __construct(private $user_id, private $totalRows) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $value) {
            try {
                $data = $value->toArray();
                $value['is_selling'] = $value['is_selling'] ?? true;
                $data['is_selling'] = in_array($value['is_selling'], ['Yes', true]) ? true : false;

                $unit = Unit::firstOrCreate(['name' => $data['unit'], 'code' => $data['unit']]);
                $data['unit_id'] = $unit->id;
                $department_id = Department::selfCreate($data['department']);
                $data['department_id'] = $department_id;

                $mainCategoryData = [
                    'name' => $data['main_category'],
                ];
                $main_category_id = Category::selfCreate($mainCategoryData);
                $data['main_category_id'] = $main_category_id;

                $subCategoryData = [
                    'parent_id' => $data['main_category_id'],
                    'name' => $data['sub_category'],
                ];
                $sub_category_id = Category::selfCreate($subCategoryData);
                $data['sub_category_id'] = $sub_category_id;

                $data['created_by'] = $this->user_id;
                $data['updated_by'] = $this->user_id;

                $exists = Product::firstWhere('name', $data['name']);
                if (! $exists) {
                    $trashedExists = Product::withTrashed()->firstWhere('name', $data['name']);
                    if ($trashedExists) {
                        $trashedExists->restore();
                        $trashedExists->update($data);
                    } else {
                        Product::create($data);
                    }
                }
            } catch (\Throwable $th) {
                $data['message'] = $th->getMessage();
                $this->errors[] = $data;
            }
        }
        $this->processedRows += count($rows);
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->user_id, 'Product', $progress));
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function __destruct()
    {
        if ($this->errors) {
            event(new FileImportCompleted($this->user_id, 'Product', $this->errors));
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
