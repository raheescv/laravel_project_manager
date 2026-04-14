<?php

namespace App\Imports;

use App\Events\FileImportProgress;
use App\Models\StockCheckItem;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockCheckItemImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private int $updatedCount = 0;

    private int $processedRows = 0;

    private array $errors = [];

    public function __construct(
        private int $stockCheckId,
        private int $userId,
        private int $totalRows,
    ) {}

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            $processedInBatch = 0;
            foreach ($rows as $row) {
                if ($row->filter()->isEmpty()) {
                    continue;
                }

                $processedInBatch++;
                $itemId = $row['item_id'] ?? null;

                $physicalQty = $row['physical_qty'] ?? null;

                if (! $itemId || $physicalQty === null) {
                    continue;
                }
                $item = StockCheckItem::where('id', $itemId)
                    ->where('stock_check_id', $this->stockCheckId)
                    ->first();

                if (! $item) {
                    $this->errors[] = "Row with Item ID {$itemId}: Item not found in this stock check.";

                    continue;
                }

                if (! is_numeric($physicalQty) || $physicalQty < 0) {
                    $this->errors[] = "Row with Item ID {$itemId}: Invalid physical quantity '{$physicalQty}'.";

                    continue;
                }
                $item->update(['physical_quantity' => $physicalQty]);
                $this->updatedCount++;
            }

            DB::commit();

            $this->processedRows += $processedInBatch;
            $this->updateProgress();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stock check item import error', ['message' => $e->getMessage()]);
            $this->errors[] = 'Import failed: '.$e->getMessage();
        }
    }

    private function updateProgress(): void
    {
        if ($this->totalRows <= 0) {
            return;
        }

        $progress = min(90, (int) (($this->processedRows / $this->totalRows) * 90) + 10);
        event(new FileImportProgress($this->userId, 'StockCheck', $progress, "{$this->updatedCount} items updated so far..."));
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
