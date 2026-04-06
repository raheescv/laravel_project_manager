<?php

namespace App\Livewire\Property\Property;

use App\Exports\Templates\PropertyImportTemplate;
use App\Jobs\Property\ImportPropertyJob;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    public $step = 1;

    public $headers = [];

    public $mappings = [];

    public $previewData = [];

    public $filePath;

    // Available fields for mapping
    public $availableFields = [
        'number' => 'Property Number (*)',
        'code' => 'Property Code',
        'property_building_id' => 'Building (ID or Name) (*)',
        'property_type_id' => 'Property Type (ID or Name) (*)',
        'property_group_id' => 'Group / Project (ID or Name)',
        'unit_no' => 'Unit No',
        'floor' => 'Floor',
        'rooms' => 'Rooms',
        'kitchen' => 'Kitchen',
        'toilet' => 'Toilet',
        'hall' => 'Hall',
        'size' => 'Size',
        'rent' => 'Rent',
        'ownership' => 'Ownership',
        'electricity' => 'Electricity',
        'kahramaa' => 'Kahramaa',
        'parking' => 'Parking',
        'furniture' => 'Furniture',
        'status' => 'Status',
        'availability_status' => 'Availability Status',
        'remark' => 'Remark',
        'description' => 'Description',
        'upload_type' => 'Upload Type (new/update)',
    ];

    private function getHeaderAliases(): array
    {
        return [
            'number' => ['number', 'propertynumber', 'property number', 'propertyno', 'property no', 'no'],
            'code' => ['code', 'propertycode', 'property code'],
            'property_building_id' => ['property_building_id', 'building', 'buildingname', 'building name', 'building_id', 'property building'],
            'property_type_id' => ['property_type_id', 'type', 'propertytype', 'property type', 'type_id'],
            'property_group_id' => ['property_group_id', 'group', 'groupname', 'group name', 'project', 'group_id', 'property group'],
            'unit_no' => ['unit_no', 'unitno', 'unit no', 'unit'],
            'floor' => ['floor', 'floorno', 'floor no'],
            'rooms' => ['rooms', 'room', 'bedrooms', 'bedroom', 'bhk'],
            'kitchen' => ['kitchen', 'kitchens'],
            'toilet' => ['toilet', 'toilets', 'bathroom', 'bathrooms'],
            'hall' => ['hall', 'halls', 'livingroom', 'living room'],
            'size' => ['size', 'area', 'sqft', 'sqm'],
            'rent' => ['rent', 'rentamount', 'rent amount', 'monthlyrent', 'monthly rent'],
            'ownership' => ['ownership', 'owned'],
            'electricity' => ['electricity'],
            'kahramaa' => ['kahramaa', 'kahramaa_no', 'kahramaa no'],
            'parking' => ['parking', 'parkings', 'parkingslots'],
            'furniture' => ['furniture', 'furnished'],
            'status' => ['status'],
            'availability_status' => ['availability_status', 'availabilitystatus', 'availability', 'availability status'],
            'remark' => ['remark', 'remarks', 'note', 'notes'],
            'description' => ['description', 'desc'],
            'upload_type' => ['upload_type', 'uploadtype', 'upload type'],
        ];
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(str_replace(['_', ' ', '-'], '', $value));
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $this->filePath = $this->file->store('temp-imports', 'public');

        $headings = (new HeadingRowImport())->toArray(Storage::disk('public')->path($this->filePath));
        $this->headers = $headings[0][0] ?? [];

        $aliases = $this->getHeaderAliases();

        foreach ($this->availableFields as $field => $label) {
            $allowed = $aliases[$field] ?? [$this->normalizeHeader($field)];
            $normalizedAllowed = array_map(fn ($a) => $this->normalizeHeader($a), $allowed);
            foreach ($this->headers as $header) {
                $normalizedHeader = $this->normalizeHeader((string) $header);
                if (in_array($normalizedHeader, $normalizedAllowed, true)) {
                    $this->mappings[$field] = $header;
                    break;
                }
            }
        }

        $this->step = 2;
        $this->loadPreview();
    }

    public function loadPreview()
    {
        $rows = Excel::toArray(new \stdClass(), Storage::disk('public')->path($this->filePath));
        $this->previewData = array_slice($rows[0], 1, 10);
    }

    public function goToStep($step)
    {
        $this->step = $step;
    }

    public function sample()
    {
        return Excel::download(new PropertyImportTemplate(), 'property_import_template.xlsx');
    }

    public function save()
    {
        $this->validate([
            'mappings.number' => 'required',
            'mappings.property_building_id' => 'required',
            'mappings.property_type_id' => 'required',
        ], [
            'mappings.number.required' => 'The Property Number field must be mapped.',
            'mappings.property_building_id.required' => 'The Building field must be mapped.',
            'mappings.property_type_id.required' => 'The Property Type field must be mapped.',
        ]);

        ImportPropertyJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('branch_id'),
            session('tenant_id'),
            $this->mappings
        );

        $this->dispatch('success', ['message' => 'Import started in background']);
        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.property.property.import', [
            'buildings' => PropertyBuilding::pluck('name', 'id'),
            'types' => PropertyType::pluck('name', 'id'),
            'groups' => PropertyGroup::pluck('name', 'id'),
        ]);
    }
}
