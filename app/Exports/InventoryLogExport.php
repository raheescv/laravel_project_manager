<?php

namespace App\Exports;

use App\Models\InventoryLog;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryLogExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = InventoryLog::with('branch:id,name', 'product:id,name,department_id,main_category_id,sub_category_id', 'product.department:id,name', 'product.subCategory:id,name', 'product.mainCategory:id,name')
            ->when($this->filters['search'], function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('batch', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%")
                        ->orWhere('quantity_in', 'like', "%{$value}%")
                        ->orWhere('quantity_out', 'like', "%{$value}%")
                        ->orWhere('balance', 'like', "%{$value}%")
                        ->orWhere('user_name', 'like', "%{$value}%");
                });
            })
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                return $query->where('created_at', '>=', date('Y-m-d H:i:s', strtotime($value)));
            })
            ->when($this->filters['to_date'] ?? '', function ($query, $value) {
                return $query->where('created_at', '<=', date('Y-m-d H:i:s', strtotime($value)));
            })
            ->when($this->filters['branch_id'] ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->filters['product_id'] ?? '', function ($query, $value) {
                return $query->where('product_id', $value);
            });

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Branch',
            'Department',
            'Main Category',
            'Sub Category',
            'Product',
            'barcode',
            'batch',
            'In',
            'out',
            'balance',
            'remarks',
            'User',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function map($row): array
    {
        return [
            $row->id,
            systemDateTime($row->created_at),
            $row->branch?->name,
            $row->product->department?->name,
            $row->product->mainCategory?->name,
            $row->product->subCategory?->name,
            $row->product?->name,
            $row->barcode,
            $row->batch,
            $row->quantity_in,
            $row->quantity_out,
            $row->balance,
            $row->remarks,
            $row->user_name,
        ];
    }
}
