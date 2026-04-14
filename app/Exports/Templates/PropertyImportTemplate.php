<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PropertyImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'number' => 'A-101',
                'code' => 'PRP-A101',
                'property_building' => 'Building A',
                'property_type' => 'Apartment',
                'property_group' => 'Project North',
                'unit_no' => '101',
                'floor' => '1',
                'rooms' => '2',
                'kitchen' => '1',
                'toilet' => '2',
                'hall' => '1',
                'size' => '950.50',
                'rent' => '4500.00',
                'ownership' => 'owned',
                'electricity' => 'separate',
                'kahramaa' => '12345',
                'parking' => '1',
                'furniture' => 'furnished',
                'status' => 'vacant',
                'availability_status' => 'available',
                'remark' => 'Sea view',
                'description' => 'Spacious 2BHK unit',
                'upload_type' => 'new',
            ],
            [
                'number' => 'A-101',
                'code' => 'PRP-A101',
                'property_building' => 'Building A',
                'property_type' => 'Apartment',
                'property_group' => 'Project North',
                'unit_no' => '101',
                'floor' => '1',
                'rooms' => '2',
                'kitchen' => '1',
                'toilet' => '2',
                'hall' => '1',
                'size' => '950.50',
                'rent' => '4800.00',
                'ownership' => 'owned',
                'electricity' => 'separate',
                'kahramaa' => '12345',
                'parking' => '1',
                'furniture' => 'furnished',
                'status' => 'vacant',
                'availability_status' => 'available',
                'remark' => 'Updated rent',
                'description' => 'Spacious 2BHK unit',
                'upload_type' => 'update',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'number',
            'code',
            'property_building',
            'property_type',
            'property_group',
            'unit_no',
            'floor',
            'rooms',
            'kitchen',
            'toilet',
            'hall',
            'size',
            'rent',
            'ownership',
            'electricity',
            'kahramaa',
            'parking',
            'furniture',
            'status',
            'availability_status',
            'remark',
            'description',
            'upload_type',
        ];
    }
}
