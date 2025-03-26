<?php

namespace App\Imports;

use App\Actions\Product\ProductPrice\CreateAction;
use App\Actions\Product\ProductPrice\UpdateAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServiceImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $processedRows = 0;

    private $errors = [];

    public function __construct(private $user_id, private $totalRows) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $value) {
            try {
                if (! $value['name']) {
                    continue;
                }
                $data = Product::constructData($value->toArray(), $this->user_id);
                $data['mrp'] = $value['price'] ?? 0;
                $data['code'] = $data['code'] ?? rand(999, 9999);
                $data['status'] = $data['status'] ?? 'active';
                $data['type'] = 'service';
                $data['cost'] = $value['price'];
                $home_service = $value['home_service'] ?? 0;
                $exists = Product::firstWhere('name', $data['name']);
                if (! $exists) {
                    $trashedExists = Product::withTrashed()->firstWhere('name', $data['name']);
                    if ($trashedExists) {
                        $trashedExists->restore();
                        $trashedExists->update($data);
                        $model = $trashedExists;
                    } else {
                        $model = Product::create($data);
                    }
                    if ($home_service) {
                        $priceCheck = ProductPrice::where('product_id', $model->id)->where('price_type', 'home_service')->first();
                        $priceData = [
                            'product_id' => $model->id,
                            'price_type' => 'home_service',
                            'amount' => $home_service,
                        ];
                        if ($priceCheck) {
                            $response = (new UpdateAction())->execute($priceData, $priceCheck->id);
                        } else {
                            $response = (new CreateAction())->execute($priceData);
                        }
                        if (! $response['success']) {
                            throw new \Exception($response['message'], 1);
                        }
                    }
                    Inventory::selfCreateByProduct($model, $this->user_id, $quantity = 0);
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
