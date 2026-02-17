<?php

namespace App\Exports;

use App\Models\TailoringOrderItem;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TailoringOrderItemReportExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $dateType = $this->filters['date_type'] ?? 'order_date';
        $allowedBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        return TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->when($this->filters['from_date'] ?? '', fn ($q) => $q->whereDate('tailoring_orders.'.$dateType, '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? '', fn ($q) => $q->whereDate('tailoring_orders.'.$dateType, '<=', $this->filters['to_date']))
            ->when($this->filters['branch_id'] ?? '', fn ($q) => $q->where('tailoring_orders.branch_id', $this->filters['branch_id']))
            ->when($this->filters['customer_id'] ?? '', fn ($q) => $q->where('tailoring_orders.account_id', $this->filters['customer_id']))
            ->when($this->filters['product_id'] ?? '', fn ($q) => $q->where('tailoring_order_items.product_id', $this->filters['product_id']))
            ->when($this->filters['category_id'] ?? '', fn ($q) => $q->where('tailoring_order_items.tailoring_category_id', $this->filters['category_id']))
            ->when($this->filters['tailor_id'] ?? '', fn ($q) => $q->where('tailoring_order_items.tailor_id', $this->filters['tailor_id']))
            ->when($this->filters['status'] ?? '', fn ($q) => $q->where('tailoring_orders.status', $this->filters['status']))
            ->when($this->filters['search'] ?? '', function ($q) {
                $term = trim($this->filters['search']);

                return $q->where(function ($q) use ($term) {
                    $q->where('tailoring_orders.order_no', 'like', "%{$term}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_color', 'like', "%{$term}%");
                });
            })
            ->whereIn('tailoring_orders.branch_id', $allowedBranchIds)
            ->leftJoin('tailoring_categories', 'tailoring_categories.id', '=', 'tailoring_order_items.tailoring_category_id')
            ->leftJoin('tailoring_category_models', 'tailoring_category_models.id', '=', 'tailoring_order_items.tailoring_category_model_id')
            ->leftJoin('tailoring_category_model_types', 'tailoring_category_model_types.id', '=', 'tailoring_order_items.tailoring_category_model_type_id')
            ->leftJoin('units', 'units.id', '=', 'tailoring_order_items.unit_id')
            ->leftJoin('users as tailors', 'tailors.id', '=', 'tailoring_order_items.tailor_id')
            ->select(
                'tailoring_order_items.*',
                'tailoring_orders.order_no',
                'tailoring_orders.order_date',
                'tailoring_orders.customer_name',
                'tailoring_categories.name as category_name',
                'tailoring_category_models.name as category_model_name',
                'tailoring_category_model_types.name as category_model_type_name',
                'units.name as unit_name',
                'tailors.name as tailor_name'
            )
            ->orderBy('tailoring_order_items.id');
    }

    protected function visibleColumns(): array
    {
        $cols = $this->filters['visible_columns'] ?? [];
        $defaults = [
            'order_no' => true, 'order_date' => true, 'customer' => true, 'item_no' => true,
            'category' => true, 'category_model' => true, 'category_model_type' => true, 'product_name' => true, 'product_color' => true,
            'unit' => true, 'quantity' => true, 'quantity_per_item' => true, 'completed_quantity' => true,
            'unit_price' => true, 'stitch_rate' => true, 'gross_amount' => true, 'discount' => true,
            'net_amount' => true, 'tax' => true, 'tax_amount' => true, 'total' => true,
            'tailor' => true, 'tailor_commission' => true, 'used_quantity' => true, 'wastage' => true,
            'item_completion_date' => true, 'is_selected_for_completion' => true, 'tailoring_notes' => true,
            'rating' => true, 'status' => true,
        ];

        return array_merge($defaults, $cols);
    }

    public function headings(): array
    {
        $vis = $this->visibleColumns();
        $out = ['#'];
        if ($vis['order_no'] ?? true) {
            $out[] = 'Order No';
        }
        if ($vis['order_date'] ?? true) {
            $out[] = 'Order Date';
        }
        if ($vis['customer'] ?? true) {
            $out[] = 'Customer';
        }
        if ($vis['item_no'] ?? true) {
            $out[] = 'Item #';
        }
        if ($vis['category'] ?? true) {
            $out[] = 'Category';
        }
        if ($vis['category_model'] ?? true) {
            $out[] = 'Model';
        }
        if ($vis['category_model_type'] ?? true) {
            $out[] = 'Model Type';
        }
        if ($vis['product_name'] ?? true) {
            $out[] = 'Product';
        }
        if ($vis['product_color'] ?? true) {
            $out[] = 'Color';
        }
        if ($vis['unit'] ?? true) {
            $out[] = 'Unit';
        }
        if ($vis['quantity'] ?? true) {
            $out[] = 'Quantity';
        }
        if ($vis['quantity_per_item'] ?? true) {
            $out[] = 'Meter Per Item';
        }
        if ($vis['completed_quantity'] ?? true) {
            $out[] = 'Completed Qty';
        }
        if ($vis['unit_price'] ?? true) {
            $out[] = 'Unit Price';
        }
        if ($vis['stitch_rate'] ?? true) {
            $out[] = 'Stitch Rate';
        }
        if ($vis['gross_amount'] ?? true) {
            $out[] = 'Gross';
        }
        if ($vis['discount'] ?? true) {
            $out[] = 'Discount';
        }
        if ($vis['net_amount'] ?? true) {
            $out[] = 'Net';
        }
        if ($vis['tax'] ?? true) {
            $out[] = 'Tax %';
        }
        if ($vis['tax_amount'] ?? true) {
            $out[] = 'Tax Amt';
        }
        if ($vis['total'] ?? true) {
            $out[] = 'Total';
        }
        if ($vis['tailor'] ?? true) {
            $out[] = 'Tailor';
        }
        if ($vis['tailor_commission'] ?? true) {
            $out[] = 'Tailor Commission';
        }
        if ($vis['used_quantity'] ?? true) {
            $out[] = 'Used Qty';
        }
        if ($vis['wastage'] ?? true) {
            $out[] = 'Wastage';
        }
        if ($vis['item_completion_date'] ?? true) {
            $out[] = 'Completion Date';
        }
        if ($vis['is_selected_for_completion'] ?? true) {
            $out[] = 'Selected for Completion';
        }
        if ($vis['tailoring_notes'] ?? true) {
            $out[] = 'Notes';
        }
        if ($vis['rating'] ?? true) {
            $out[] = 'Rating';
        }
        if ($vis['status'] ?? true) {
            $out[] = 'Item Status';
        }

        return $out;
    }

    public function map($row): array
    {
        $vis = $this->visibleColumns();
        $statusLabels = tailoringOrderItemStatuses();
        $out = [$row->id];
        if ($vis['order_no'] ?? true) {
            $out[] = $row->order_no ?? '';
        }
        if ($vis['order_date'] ?? true) {
            $out[] = isset($row->order_date) ? systemDate($row->order_date) : '';
        }
        if ($vis['customer'] ?? true) {
            $out[] = $row->customer_name ?? '';
        }
        if ($vis['item_no'] ?? true) {
            $out[] = $row->item_no ?? '';
        }
        if ($vis['category'] ?? true) {
            $out[] = $row->category_name ?? '';
        }
        if ($vis['category_model'] ?? true) {
            $out[] = $row->category_model_name ?? '';
        }
        if ($vis['category_model_type'] ?? true) {
            $out[] = $row->category_model_type_name ?? '';
        }
        if ($vis['product_name'] ?? true) {
            $out[] = $row->product_name ?? '';
        }
        if ($vis['product_color'] ?? true) {
            $out[] = $row->product_color ?? '';
        }
        if ($vis['unit'] ?? true) {
            $out[] = $row->unit_name ?? '';
        }
        if ($vis['quantity'] ?? true) {
            $out[] = $row->quantity ?? '';
        }
        if ($vis['quantity_per_item'] ?? true) {
            $out[] = $row->quantity_per_item !== null ? $row->quantity_per_item : '';
        }
        if ($vis['completed_quantity'] ?? true) {
            $out[] = $row->completed_quantity !== null ? $row->completed_quantity : '';
        }
        if ($vis['unit_price'] ?? true) {
            $out[] = $row->unit_price ?? '';
        }
        if ($vis['stitch_rate'] ?? true) {
            $out[] = $row->stitch_rate ?? '';
        }
        if ($vis['gross_amount'] ?? true) {
            $out[] = $row->gross_amount ?? '';
        }
        if ($vis['discount'] ?? true) {
            $out[] = $row->discount ?? '';
        }
        if ($vis['net_amount'] ?? true) {
            $out[] = $row->net_amount ?? '';
        }
        if ($vis['tax'] ?? true) {
            $out[] = $row->tax !== null && $row->tax !== '' ? $row->tax : '';
        }
        if ($vis['tax_amount'] ?? true) {
            $out[] = $row->tax_amount ?? '';
        }
        if ($vis['total'] ?? true) {
            $out[] = $row->total ?? '';
        }
        if ($vis['tailor'] ?? true) {
            $out[] = $row->tailor_name ?? '';
        }
        if ($vis['tailor_commission'] ?? true) {
            $out[] = $row->tailor_commission ?? '';
        }
        if ($vis['used_quantity'] ?? true) {
            $out[] = $row->used_quantity !== null ? $row->used_quantity : '';
        }
        if ($vis['wastage'] ?? true) {
            $out[] = $row->wastage !== null ? $row->wastage : '';
        }
        if ($vis['item_completion_date'] ?? true) {
            $out[] = $row->item_completion_date ? systemDate($row->item_completion_date) : '';
        }
        if ($vis['is_selected_for_completion'] ?? true) {
            $out[] = $row->is_selected_for_completion ? 'Yes' : 'No';
        }
        if ($vis['tailoring_notes'] ?? true) {
            $out[] = $row->tailoring_notes ?? '';
        }
        if ($vis['rating'] ?? true) {
            $out[] = $row->rating !== null && $row->rating > 0 ? $row->rating.'/5' : '';
        }
        if ($vis['status'] ?? true) {
            $out[] = $statusLabels[$row->status] ?? $row->status;
        }

        return $out;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
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
                $totalRows = $sheet->getHighestRow() + 1;
                if ($totalRows > 2) {
                    $sheet->getStyle("A{$totalRows}:Z{$totalRows}")->getFont()->setBold(true);
                }
            },
        ];
    }
}
