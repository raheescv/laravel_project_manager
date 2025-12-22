<?php

namespace App\Exports;

use App\Actions\Product\ProductReportAction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return (new ProductReportAction())->execute($this->filters);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product Name',
            'Code',
            'Barcode',
            'Category',
            'Current Stock',
            'Total Sold',
            'Total Purchased',
            'Transfer In',
            'Transfer Out',
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
            $row->name,
            $row->code,
            $row->barcode,
            $row->category_name,
            $row->current_stock ?? 0,
            $row->total_sold ?? 0,
            $row->total_purchased ?? 0,
            $row->transfer_in ?? 0,
            $row->transfer_out ?? 0,
        ];
    }
}
