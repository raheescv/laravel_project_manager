<?php

namespace App\Imports;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Checklist;
use App\Models\PropertyType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ChecklistItemImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    private array $propertyTypeCache = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private ?int $tenantId = null,
        private array $mappings = [],
        private string $duplicateStrategy = 'skip'
    ) {}

    public function collection(Collection $rows)
    {
        $nameHeader = $this->mappings['name'] ?? 'name';

        $filteredRows = $rows->filter(function ($row) use ($nameHeader) {
            return $row->filter()->isNotEmpty() && ! empty($row[$nameHeader]);
        });

        $processedInBatch = 0;

        foreach ($filteredRows as $value) {
            try {
                $processedInBatch++;
                $this->processChecklistItemRow($value);
            } catch (\Throwable $th) {
                $this->handleError($value, $th);
            }
        }

        $this->processedRows += $processedInBatch;
        $this->updateProgress();
    }

    private function getMappedValue($row, string $field): mixed
    {
        $excelHeader = $this->mappings[$field] ?? $field;

        return $row[$excelHeader] ?? null;
    }

    private function processChecklistItemRow($value): void
    {
        $name = trim((string) $this->getMappedValue($value, 'name'));
        if ($name === '') {
            return;
        }

        $category = $this->trimOrNull($this->getMappedValue($value, 'category'));
        $propertyTypeId = $this->resolvePropertyType($this->trimOrNull($this->getMappedValue($value, 'property_type')));

        $data = [
            'tenant_id' => $this->tenantId,
            'name' => $name,
            'category' => $category,
            'property_type_id' => $propertyTypeId,
        ];

        // Only touch sort_order / is_active when the sheet actually carries them,
        // so an "update" pass can't silently reset values the user set by hand.
        if ($this->isMapped('sort_order')) {
            $data['sort_order'] = $this->toIntOrNull($this->getMappedValue($value, 'sort_order')) ?? 0;
        }
        if ($this->isMapped('is_active')) {
            $data['is_active'] = $this->toBool($this->getMappedValue($value, 'is_active'));
        }

        // Same identity rule the manual create action uses: name + category +
        // property type. Soft-deleted rows count as duplicates so a re-import
        // revives them instead of stacking a second copy.
        $existing = Checklist::withTrashed()
            ->where('tenant_id', $this->tenantId)
            ->where('name', $name)
            ->when($category !== null, fn ($q) => $q->where('category', $category), fn ($q) => $q->whereNull('category'))
            ->when($propertyTypeId, fn ($q) => $q->where('property_type_id', $propertyTypeId), fn ($q) => $q->whereNull('property_type_id'))
            ->first();

        if ($existing) {
            if ($this->duplicateStrategy === 'update') {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($data);
            }

            return; // skip
        }

        Checklist::create($data + ['created_by' => $this->userId]);
    }

    /** True when the user mapped an Excel column onto this field. */
    private function isMapped(string $field): bool
    {
        return ! empty($this->mappings[$field] ?? '');
    }

    /** Resolve a property type by name, creating it when the name is new. */
    private function resolvePropertyType(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        $key = strtolower($name);
        if (array_key_exists($key, $this->propertyTypeCache)) {
            return $this->propertyTypeCache[$key];
        }

        $type = PropertyType::where('tenant_id', $this->tenantId)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->first();

        if (! $type) {
            $type = PropertyType::create([
                'tenant_id' => $this->tenantId,
                'name' => $name,
                'created_by' => $this->userId,
            ]);
        }

        $this->propertyTypeCache[$key] = $type->id;

        return $type->id;
    }

    private function trimOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function toIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /** Blank means active — most sheets only flag the exceptions. */
    private function toBool($value): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return true;
        }

        return ! in_array(strtolower(trim((string) $value)), ['0', 'no', 'n', 'false', 'inactive', 'disabled'], true);
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData = $value instanceof Collection ? $value->toArray() : (array) $value;
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('Checklist item import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = min(($this->processedRows / max($this->totalRows, 1)) * 100, 100);
        event(new FileImportProgress($this->userId, 'ChecklistItem', $progress));
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function __destruct()
    {
        if (! empty($this->errors)) {
            event(new FileImportCompleted($this->userId, 'ChecklistItem', $this->errors));
        }
    }
}
