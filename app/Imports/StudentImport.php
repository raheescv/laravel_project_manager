<?php

namespace App\Imports;

use App\Actions\Student\CreateAction;
use App\Actions\Student\UpdateAction;
use App\Events\FileImportProgress;
use App\Support\Student\StudentImportSheet;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Students and their parents from an uploaded sheet.
 *
 * Every chunk is first reviewed by StudentImportSheet — the same check the import
 * screen showed — so rows flagged there are reported here and never written. The
 * rest go through Student\CreateAction / UpdateAction, which bring the form's own
 * validation and parent matching (by mobile). A row is matched to an existing
 * student by admission number; `duplicateStrategy` decides whether that row
 * updates the student or is skipped. Each row commits on its own, so one bad row
 * is reported and the rest still import.
 */
class StudentImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    /** Sheet line of the next row; the heading is line 1. */
    private int $line = 2;

    private StudentImportSheet $sheet;

    /** @var array<int, array{row: int, admission_no: string, name: string, message: string}> */
    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /**
     * @param  array<string, string>  $mappings  field => heading key; empty for the template's own headings
     * @param  (Closure(int): void)|null  $onProgress  called with the percentage after each chunk
     */
    public function __construct(
        private int $userId,
        private int $totalRows,
        string $duplicateStrategy = 'skip',
        array $mappings = [],
        private ?Closure $onProgress = null,
    ) {
        $this->sheet = new StudentImportSheet($mappings, $duplicateStrategy);
    }

    public function collection(Collection $rows)
    {
        $lines = [];
        foreach ($rows as $row) {
            $lines[$this->line++] = $row instanceof Collection ? $row->all() : (array) $row;
        }

        foreach ($this->sheet->review($lines) as $entry) {
            $this->processedRows++;

            if ($entry['status'] === 'skip') {
                $this->skipped++;

                continue;
            }
            if ($entry['status'] === 'error') {
                $this->fail($entry, implode(' ', $entry['issues']));

                continue;
            }

            try {
                DB::beginTransaction();
                $data = $entry['data'];
                if ($entry['account_id']) {
                    // A profile update never swaps a card (see Student\UpdateAction).
                    unset($data['card_uid']);
                    $response = (new UpdateAction())->execute($data, (int) $entry['account_id'], $this->userId);
                } else {
                    $response = (new CreateAction())->execute($data, $this->userId);
                }
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
                DB::commit();

                $entry['account_id'] ? $this->updated++ : $this->created++;
            } catch (\Throwable $th) {
                DB::rollBack();
                $this->fail($entry, $th->getMessage());
            }
        }

        $progress = min(99, (int) round($this->processedRows / max(1, $this->totalRows) * 100));
        event(new FileImportProgress($this->userId, 'Student', $progress));
        if ($this->onProgress) {
            ($this->onProgress)($progress);
        }
    }

    private function fail(array $entry, string $message): void
    {
        $this->errors[] = ['row' => $entry['line'], 'admission_no' => $entry['admission_no'], 'name' => $entry['name'], 'message' => $message];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
