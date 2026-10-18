<?php

namespace App\Exports\Templates;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductImportTemplate implements FromArray, WithHeadings
{
    /**
     * @param  array<int, string>  $branchNames  one stock column per branch; empty keeps the single `stock` column
     */
    public function __construct(private array $branchNames = []) {}

    public function array(): array
    {
        return array_map(fn (array $row) => $this->expandStock($row, $row['stock']), $this->rows());
    }

    public function headings(): array
    {
        return array_keys($this->expandStock(array_flip($this->baseHeadings()), null));
    }

    /**
     * Swaps the `stock` key for one `stock_<branch>` key per branch, keeping its position.
     */
    private function expandStock(array $row, ?string $quantity): array
    {
        if (count($this->branchNames) < 2) {
            return $row;
        }

        $expanded = [];
        foreach ($row as $key => $value) {
            if ($key !== 'stock') {
                $expanded[$key] = $value;

                continue;
            }
            foreach ($this->branchNames as $branchName) {
                $expanded['stock_'.Str::snake(Str::lower($branchName))] = $quantity;
            }
        }

        return $expanded;
    }

    private function rows(): array
    {
        return [
            [
                'name' => 'Sample Product',
                'code' => 'PRD001',
                'name_arabic' => 'منتج عينة',
                'unit' => 'PCS',
                'department' => 'Electronics',
                'main_category' => 'Mobile',
                'sub_category' => 'Smartphone',
                'hsn_code' => '12345',
                'tax' => '5',
                'description' => 'Sample product description',
                'is_selling' => '1',
                'is_favorite' => '0',
                'cost' => '100',
                'mrp' => '150',
                'barcode' => '123456789',
                'pattern' => 'Pattern1',
                'color' => 'Red',
                'size' => 'Large',
                'model' => 'XYZ123',
                'brand' => 'Brand1',
                'part_no' => 'PT001',
                'min_stock' => '10',
                'max_stock' => '100',
                'location' => 'Shelf A1',
                'reorder_level' => '20',
                'plu' => '001',
                'stock' => '50',
                'upload_type' => 'new',
                'image' => 'https://example.com/images/prd001-front.jpg, https://example.com/images/prd001-back.jpg',
            ],
            [
                'name' => 'Sample Product Update',
                'code' => 'PRD001',
                'name_arabic' => 'منتج عينة',
                'unit' => 'PCS',
                'department' => 'Electronics',
                'main_category' => 'Mobile',
                'sub_category' => 'Smartphone',
                'hsn_code' => '12345',
                'tax' => '5',
                'description' => 'Sample product description',
                'is_selling' => '1',
                'is_favorite' => '0',
                'cost' => '100',
                'mrp' => '150',
                'barcode' => '123456789',
                'pattern' => 'Pattern1',
                'color' => 'Red',
                'size' => 'Large',
                'model' => 'XYZ123',
                'brand' => 'Brand1',
                'part_no' => 'PT001',
                'min_stock' => '10',
                'max_stock' => '100',
                'location' => 'Shelf A1',
                'reorder_level' => '20',
                'plu' => '001',
                'stock' => '50',
                'upload_type' => 'update',
                'image' => 'https://example.com/images/prd001-front.jpg',
            ],
        ];
    }

    private function baseHeadings(): array
    {
        return [
            'name',
            'code',
            'name_arabic',
            'unit',
            'department',
            'main_category',
            'sub_category',
            'hsn_code',
            'tax',
            'description',
            'is_selling',
            'is_favorite',
            'cost',
            'mrp',
            'barcode',
            'pattern',
            'color',
            'size',
            'model',
            'brand_id',
            'part_no',
            'min_stock',
            'max_stock',
            'location',
            'reorder_level',
            'plu',
            'stock',
            'upload_type',
            'image',
        ];
    }
}
