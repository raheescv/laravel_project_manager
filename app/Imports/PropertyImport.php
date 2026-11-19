<?php

namespace App\Imports;

use App\Enums\Property\BuildingOwnership;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PropertyImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    /** @var array<string,int> name => id */
    private array $buildingsByName = [];

    /** @var array<string,int> name => id */
    private array $typesByName = [];

    /** @var array<string,int> name => id */
    private array $groupsByName = [];

    /** @var array<int,int> buildingId => groupId */
    private array $buildingGroups = [];

    /** @var array<string,int> "buildingId|number" => propertyId */
    private array $existingProperties = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private ?int $branchId = null,
        private array $mappings = []
    ) {}

    public function collection(Collection $rows)
    {
        $this->preloadLookups();
        $this->preloadExistingProperties($rows);

        $numberCol = $this->mappings['number'] ?? 'number';

        $filteredRows = $rows->filter(function ($row) use ($numberCol) {
            return $row->filter()->isNotEmpty() && ! empty($row[$numberCol]);
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
        $buildings = PropertyBuilding::get(['id', 'name', 'property_group_id']);
        $this->buildingsByName = $buildings
            ->mapWithKeys(fn (PropertyBuilding $building) => [strtolower(trim((string) $building->name)) => $building->id])
            ->toArray();
        $this->buildingGroups = $buildings->pluck('property_group_id', 'id')->toArray();

        $this->typesByName = PropertyType::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim((string) $name)) => $id])
            ->toArray();

        $this->groupsByName = PropertyGroup::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim((string) $name)) => $id])
            ->toArray();
    }

    private function preloadExistingProperties(Collection $rows): void
    {
        $numberCol = $this->mappings['number'] ?? 'number';
        $numbers = $rows->pluck($numberCol)->filter()->unique()->toArray();

        if (empty($numbers)) {
            return;
        }

        $existing = Property::whereIn('number', $numbers)->get(['id', 'number', 'property_building_id']);

        foreach ($existing as $p) {
            $this->existingProperties[$p->property_building_id.'|'.strtolower((string) $p->number)] = $p->id;
        }
    }

    private function processRow($value): void
    {
        $row = [];
        foreach ($this->mappings as $internalField => $excelHeader) {
            if ($excelHeader !== null && $excelHeader !== '' && isset($value[$excelHeader])) {
                $row[$internalField] = $value[$excelHeader];
            }
        }

        $number = isset($row['number']) ? trim((string) $row['number']) : '';
        if ($number === '') {
            return;
        }
        $row['number'] = $number;

        $row['property_group_id'] = $this->resolveGroup($row['property_group_id'] ?? null);
        $row['property_building_id'] = $this->resolveBuilding($row['property_building_id'] ?? null, $row);
        $row['property_type_id'] = $this->resolveType($row['property_type_id'] ?? null);

        if (empty($row['property_building_id'])) {
            throw new Exception('Building could not be resolved for row "'.$number.'".');
        }
        if (empty($row['property_type_id'])) {
            throw new Exception('Property Type could not be resolved for row "'.$number.'".');
        }
        if (empty($row['property_group_id'])) {
            $row['property_group_id'] = $this->buildingGroups[$row['property_building_id']]
                ?? PropertyBuilding::whereKey($row['property_building_id'])->value('property_group_id');
        }

        // Branch
        if ($this->branchId && empty($row['branch_id'])) {
            $row['branch_id'] = $this->branchId;
        }

        $uploadType = strtolower((string) ($row['upload_type'] ?? 'new'));
        unset($row['upload_type']);

        // Cast numeric fields
        foreach (['rooms', 'kitchen', 'toilet', 'hall', 'parking'] as $intField) {
            if (isset($row[$intField]) && $row[$intField] !== '') {
                $row[$intField] = (int) $row[$intField];
            }
        }
        foreach (['size', 'rent'] as $decField) {
            if (isset($row[$decField]) && $row[$decField] !== '') {
                $row[$decField] = (float) $row[$decField];
            }
        }

        $key = $row['property_building_id'].'|'.strtolower($number);

        if ($uploadType === 'update') {
            $existingId = $this->existingProperties[$key] ?? null;
            if (! $existingId) {
                throw new Exception('Property not found for update: building '.$row['property_building_id'].' / number '.$number);
            }
            validationHelper(Property::rules($existingId), $row, 'Property');
            $row['updated_by'] = $this->userId;
            Property::whereKey($existingId)->update($row);

            return;
        }

        // new
        if (isset($this->existingProperties[$key])) {
            return; // skip duplicates silently
        }

        validationHelper(Property::rules(), $row, 'Property');
        $row['created_by'] = $this->userId;
        $created = Property::create($row);
        $this->existingProperties[$key] = $created->id;
    }

    /**
     * Resolve a building by ID or name, creating it under the row's group when the name is new.
     *
     * @param  array<string,mixed>  $row
     */
    private function resolveBuilding($value, array $row): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $name = trim((string) $value);
        $key = strtolower($name);
        if (isset($this->buildingsByName[$key])) {
            return $this->buildingsByName[$key];
        }

        $groupId = $row['property_group_id'] ?? null;
        $branchId = $row['branch_id'] ?? $this->branchId;
        if (! $groupId || ! $branchId) {
            return null;
        }

        $building = PropertyBuilding::create([
            'branch_id' => $branchId,
            'property_group_id' => $groupId,
            'name' => $name,
            'ownership' => $this->buildingOwnership($row['ownership'] ?? null),
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);
        $this->buildingsByName[$key] = $building->id;
        $this->buildingGroups[$building->id] = (int) $groupId;

        return $building->id;
    }

    /**
     * Resolve a property type by ID or name, creating it when the name is new.
     */
    private function resolveType($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $name = trim((string) $value);
        $key = strtolower($name);

        return $this->typesByName[$key] ??= PropertyType::create([
            'name' => $name,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ])->id;
    }

    /**
     * Resolve a property group by ID or name, creating it when the name is new.
     */
    private function resolveGroup($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $name = trim((string) $value);
        $key = strtolower($name);
        if (! isset($this->groupsByName[$key]) && ! $this->branchId) {
            return null;
        }

        return $this->groupsByName[$key] ??= PropertyGroup::create([
            'branch_id' => $this->branchId,
            'name' => $name,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ])->id;
    }

    /**
     * Map a unit's ownership wording (owned / leased / rented) onto the building ownership enum.
     */
    private function buildingOwnership($value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match (true) {
            str_starts_with($normalized, 'lease') => BuildingOwnership::Lease->value,
            str_starts_with($normalized, 'rent') => BuildingOwnership::Rent->value,
            default => BuildingOwnership::Own->value,
        };
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData = $value->toArray();
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('Property import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = $this->totalRows > 0 ? ($this->processedRows / $this->totalRows) * 100 : 100;
        event(new FileImportProgress($this->userId, 'Property', $progress));
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
            event(new FileImportCompleted($this->userId, 'Property', $this->errors));
        }
    }
}
