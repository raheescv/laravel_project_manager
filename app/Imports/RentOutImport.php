<?php

namespace App\Imports;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Account;
use App\Models\Property;
use App\Models\RentOut;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RentOutImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    /** @var array<string,int> lower(number) => id */
    private array $propertiesByNumber = [];

    /** @var array<string,int> lower(name) => id */
    private array $accountsByName = [];

    /** @var array<string,int> lower(name) => id */
    private array $usersByName = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private ?int $branchId = null,
        private string $agreementType = 'rental',
        private array $mappings = []
    ) {}

    public function collection(Collection $rows)
    {
        $this->preloadLookups();

        $propertyCol = $this->mappings['property_id'] ?? 'property_id';

        $filteredRows = $rows->filter(function ($row) use ($propertyCol) {
            return $row->filter()->isNotEmpty() && ! empty($row[$propertyCol]);
        });

        $processedInBatch = 0;

        foreach ($filteredRows as $value) {
            try {
                $processedInBatch++;
                $this->processRow($value);
            } catch (\Throwable $th) {
                $this->handleError($value, $th);
            }
        }

        $this->processedRows += $processedInBatch;
        $this->updateProgress();
    }

    private function preloadLookups(): void
    {
        $this->propertiesByNumber = Property::pluck('id', 'number')
            ->mapWithKeys(fn ($id, $number) => [strtolower(trim((string) $number)) => $id])
            ->toArray();

        $this->accountsByName = Account::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim((string) $name)) => $id])
            ->toArray();

        $this->usersByName = User::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim((string) $name)) => $id])
            ->toArray();
    }

    private function processRow($value): void
    {
        $row = [];
        foreach ($this->mappings as $internalField => $excelHeader) {
            if ($excelHeader !== null && $excelHeader !== '' && isset($value[$excelHeader])) {
                $row[$internalField] = $value[$excelHeader];
            }
        }

        // Resolve foreign keys (accept either ID or name/number)
        $row['property_id'] = $this->resolveProperty($row['property_id'] ?? null);
        $row['account_id'] = $this->resolveAccount($row['account_id'] ?? null);
        if (isset($row['salesman_id']) && $row['salesman_id'] !== '') {
            $row['salesman_id'] = $this->resolveUser($row['salesman_id']);
        } else {
            unset($row['salesman_id']);
        }

        if (empty($row['property_id'])) {
            throw new Exception('Property could not be resolved.');
        }
        if (empty($row['account_id'])) {
            throw new Exception('Customer / Account could not be resolved.');
        }

        // Dates
        foreach (['start_date', 'end_date', 'vacate_date'] as $dateField) {
            if (isset($row[$dateField]) && $row[$dateField] !== '') {
                $row[$dateField] = $this->parseDate($row[$dateField]);
            }
        }

        // Numerics
        foreach (['rent', 'discount', 'total', 'management_fee', 'down_payment', 'no_of_terms', 'free_month'] as $numField) {
            if (isset($row[$numField]) && $row[$numField] !== '') {
                $row[$numField] = (float) $row[$numField];
            }
        }
        if (isset($row['collection_starting_day']) && $row['collection_starting_day'] !== '') {
            $row['collection_starting_day'] = (int) $row['collection_starting_day'];
        }

        // Defaults
        $row['agreement_type'] = $row['agreement_type'] ?? $this->agreementType;
        $row['booking_type'] = $row['booking_type'] ?? 'agreement';
        $row['status'] = $row['status'] ?? 'occupied';
        $row['booking_status'] = $row['booking_status'] ?? 'completed';
        $row['payment_frequency'] = $row['payment_frequency'] ?? 'monthly';
        $row['collection_payment_mode'] = $row['collection_payment_mode'] ?? 'cash';
        $row['collection_starting_day'] = $row['collection_starting_day'] ?? 1;
        $row['discount'] = $row['discount'] ?? 0;
        $row['rent'] = $row['rent'] ?? 0;

        // Auto compute total if not provided
        if (empty($row['total']) && ! empty($row['start_date']) && ! empty($row['end_date'])) {
            try {
                $start = Carbon::parse($row['start_date']);
                $end = Carbon::parse($row['end_date']);
                $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);
                if ($end->day >= $start->day) {
                    $months++;
                }
                $months = max($months, 0);
                $row['total'] = ((float) $row['rent']) * $months - ((float) ($row['discount'] ?? 0));
            } catch (\Throwable $e) {
                // ignore - validation will catch
            }
        }

        if ($this->branchId && empty($row['branch_id'])) {
            $row['branch_id'] = $this->branchId;
        }

        $uploadType = strtolower((string) ($row['upload_type'] ?? 'new'));
        unset($row['upload_type']);

        if ($uploadType === 'update') {
            $existingId = $row['id'] ?? null;
            if (! $existingId) {
                throw new Exception('Update requires an "id" column to identify the existing rent out.');
            }
            $model = RentOut::find($existingId);
            if (! $model) {
                throw new Exception('RentOut not found with id: '.$existingId);
            }
            validationHelper(RentOut::rules($existingId), $row, 'RentOut');
            $row['updated_by'] = $this->userId;
            $model->update($row);

            return;
        }

        validationHelper(RentOut::rules(), $row, 'RentOut');
        $row['created_by'] = $this->userId;
        RentOut::create($row);
    }

    private function resolveProperty($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $key = strtolower(trim((string) $value));

        return $this->propertiesByNumber[$key] ?? null;
    }

    private function resolveAccount($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $key = strtolower(trim((string) $value));

        return $this->accountsByName[$key] ?? null;
    }

    private function resolveUser($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $key = strtolower(trim((string) $value));

        return $this->usersByName[$key] ?? null;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            // Excel serial date
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new Exception('Invalid date value: '.$value);
        }
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData = $value->toArray();
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('RentOut import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = $this->totalRows > 0 ? ($this->processedRows / $this->totalRows) * 100 : 100;
        event(new FileImportProgress($this->userId, 'RentOut', $progress));
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function __destruct()
    {
        if (! empty($this->errors)) {
            event(new FileImportCompleted($this->userId, 'RentOut', $this->errors));
        }
    }
}
