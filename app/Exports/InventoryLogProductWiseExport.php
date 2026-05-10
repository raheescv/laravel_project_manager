<?php

namespace App\Exports;

use App\Models\InventoryLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InventoryLogProductWiseExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function collection(): Collection
    {
        $rows = $this->query()->get();

        return $rows->push((object) [
            'is_summary' => true,
            'quantity_in' => $rows->sum(fn ($row): float => (float) $row->quantity_in),
            'quantity_out' => $rows->sum(fn ($row): float => (float) $row->quantity_out),
            'balance' => $rows->sum(fn ($row): float => (float) $row->balance),
            'total_cost' => $rows->sum(fn ($row): float => (float) $row->balance * (float) $row->cost),
        ]);
    }

    public function query(): Builder
    {
        $latestDates = $this->baseQuery()
            ->select('inventory_logs.product_id', DB::raw('MAX(inventory_logs.created_at) as latest_created_at'))
            ->groupBy('inventory_logs.product_id');

        $latestIds = InventoryLog::query()
            ->joinSub($latestDates, 'latest_inventory_log_dates', function ($join): void {
                $join->on('inventory_logs.product_id', '=', 'latest_inventory_log_dates.product_id')
                    ->on('inventory_logs.created_at', '=', 'latest_inventory_log_dates.latest_created_at');
            })
            ->selectRaw('MAX(inventory_logs.id)')
            ->groupBy('inventory_logs.product_id');

        return InventoryLog::with([
            'branch:id,name',
            'product:id,name,department_id,main_category_id,sub_category_id',
            'product.department:id,name',
            'product.mainCategory:id,name',
        ])
            ->whereIn('inventory_logs.id', $latestIds)
            ->orderBy('inventory_logs.created_at', 'desc')
            ->orderBy('inventory_logs.id', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'As Of Date',
            'Branch',
            'Department',
            'Main Category',
            'Product',
            'Barcode',
            'Batch',
            'In',
            'Out',
            'Balance',
            'Cost',
            'Total Cost',
            'Remarks',
            'User',
        ];
    }

    public function map($row): array
    {
        if ($row->is_summary ?? false) {
            return [
                '',
                '',
                '',
                '',
                '',
                'TOTAL',
                '',
                '',
                (float) $row->quantity_in,
                (float) $row->quantity_out,
                (float) $row->balance,
                '',
                (float) $row->total_cost,
                '',
                '',
            ];
        }

        return [
            $row->id,
            systemDateTime($row->created_at),
            $row->branch?->name,
            $row->product->department?->name,
            $row->product->mainCategory?->name,
            $row->product?->name,
            $row->barcode,
            $row->batch,
            (float) $row->quantity_in,
            (float) $row->quantity_out,
            (float) $row->balance,
            (float) $row->cost,
            (float) $row->balance * (float) $row->cost,
            $row->remarks,
            $row->user_name,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->freezePane('A2');
                $sheet->getStyle('A1:O1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0D6EFD'],
                    ],
                ]);

                if ($lastRow > 1) {
                    $sheet->getStyle("A{$lastRow}:O{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E3F2FD'],
                        ],
                    ]);
                }
            },
        ];
    }

    private function baseQuery(): Builder
    {
        return InventoryLog::query()
            ->join('products', 'inventory_logs.product_id', '=', 'products.id')
            ->where('products.type', 'product')
            ->when($this->filters['to_date'] ?? '', function (Builder $query, string $value): Builder {
                return $query->where('inventory_logs.created_at', '<=', Carbon::parse($value)->endOfDay());
            })
            ->when($this->filters['branch_id'] ?? '', function (Builder $query, string $value): Builder {
                return $query->where('inventory_logs.branch_id', $value);
            })
            ->when($this->filters['product_id'] ?? '', function (Builder $query, string $value): Builder {
                return $query->where('inventory_logs.product_id', $value);
            })
            ->when($this->filters['search'] ?? '', function (Builder $query, string $value): Builder {
                $value = trim($value);

                return $query->where(function (Builder $query) use ($value): void {
                    $query->where('products.name', 'like', "%{$value}%")
                        ->orWhere('inventory_logs.batch', 'like', "%{$value}%")
                        ->orWhere('inventory_logs.barcode', 'like', "%{$value}%")
                        ->orWhere('inventory_logs.remarks', 'like', "%{$value}%")
                        ->orWhere('inventory_logs.user_name', 'like', "%{$value}%");
                });
            });
    }
}
