<?php

namespace App\Exports;

use App\Models\TailoringOrderItemTailor;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class TailoringOrderItemTailorReportExport implements FromQuery, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $dateType = $this->filters['date_type'] ?? 'order_date';
        $allowedBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        return TailoringOrderItemTailor::query()
            ->join('tailoring_order_items', 'tailoring_order_items.id', '=', 'tailoring_order_item_tailors.tailoring_order_item_id')
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->leftJoin('tailoring_categories', 'tailoring_categories.id', '=', 'tailoring_order_items.tailoring_category_id')
            ->leftJoin('tailoring_category_models', 'tailoring_category_models.id', '=', 'tailoring_order_items.tailoring_category_model_id')
            ->leftJoin('tailoring_category_model_types', 'tailoring_category_model_types.id', '=', 'tailoring_order_items.tailoring_category_model_type_id')
            ->leftJoin('users as tailors', 'tailors.id', '=', 'tailoring_order_item_tailors.tailor_id')
            ->when($dateType === 'completion_date', function ($q) {
                return $q->when($this->filters['from_date'] ?? '', fn ($q) => $q->whereDate('tailoring_order_item_tailors.completion_date', '>=', $this->filters['from_date']))
                    ->when($this->filters['to_date'] ?? '', fn ($q) => $q->whereDate('tailoring_order_item_tailors.completion_date', '<=', $this->filters['to_date']));
            }, function ($q) use ($dateType) {
                return $q->when($this->filters['from_date'] ?? '', fn ($q) => $q->whereDate('tailoring_orders.'.$dateType, '>=', $this->filters['from_date']))
                    ->when($this->filters['to_date'] ?? '', fn ($q) => $q->whereDate('tailoring_orders.'.$dateType, '<=', $this->filters['to_date']));
            })
            ->when($this->filters['branch_id'] ?? '', fn ($q) => $q->where('tailoring_orders.branch_id', $this->filters['branch_id']))
            ->when($this->filters['customer_id'] ?? '', fn ($q) => $q->where('tailoring_orders.account_id', $this->filters['customer_id']))
            ->when($this->filters['product_id'] ?? '', fn ($q) => $q->where('tailoring_order_items.product_id', $this->filters['product_id']))
            ->when($this->filters['category_id'] ?? '', fn ($q) => $q->where('tailoring_order_items.tailoring_category_id', $this->filters['category_id']))
            ->when($this->filters['tailor_id'] ?? '', fn ($q) => $q->where('tailoring_order_item_tailors.tailor_id', $this->filters['tailor_id']))
            ->when($this->filters['status'] ?? '', fn ($q) => $q->whereIn('tailoring_order_item_tailors.status', $this->filters['status']))
            ->when($this->filters['search'] ?? '', function ($q) {
                $term = trim($this->filters['search']);

                return $q->where(function ($q) use ($term) {
                    $q->where('tailoring_orders.order_no', 'like', "%{$term}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_color', 'like', "%{$term}%")
                        ->orWhere('tailors.name', 'like', "%{$term}%");
                });
            })
            ->whereIn('tailoring_orders.branch_id', $allowedBranchIds)
            ->select(
                'tailoring_order_item_tailors.*',
                'tailoring_orders.order_no',
                'tailoring_orders.order_date',
                'tailoring_orders.customer_name',
                'tailoring_order_items.item_no',
                'tailoring_order_items.product_name',
                'tailoring_order_items.product_color',
                'tailoring_order_items.quantity as item_quantity',
                'tailoring_categories.name as category_name',
                'tailoring_category_models.name as category_model_name',
                'tailoring_category_model_types.name as category_model_type_name',
                'tailors.name as tailor_name'
            )
            ->orderBy('tailoring_order_item_tailors.id');
    }

    protected function visibleColumns(): array
    {
        $cols = $this->filters['visible_columns'] ?? [];
        $defaults = [
            'order_no' => true,
            'order_date' => true,
            'customer' => true,
            'item_no' => true,
            'category' => true,
            'category_model' => true,
            'category_model_type' => true,
            'product_name' => true,
            'product_color' => true,
            'item_quantity' => true,
            'tailor' => true,
            'tailor_commission' => true,
            'completion_date' => true,
            'rating' => true,
            'status' => true,
            'created_at' => true,
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
        if ($vis['item_quantity'] ?? true) {
            $out[] = 'Item Qty';
        }
        if ($vis['tailor'] ?? true) {
            $out[] = 'Tailor';
        }
        if ($vis['tailor_commission'] ?? true) {
            $out[] = 'Commission';
        }
        if ($vis['completion_date'] ?? true) {
            $out[] = 'Completion Date';
        }
        if ($vis['rating'] ?? true) {
            $out[] = 'Rating';
        }
        if ($vis['status'] ?? true) {
            $out[] = 'Tailor Status';
        }
        if ($vis['created_at'] ?? true) {
            $out[] = 'Assigned At';
        }

        return $out;
    }

    public function map($row): array
    {
        $vis = $this->visibleColumns();
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
        if ($vis['item_quantity'] ?? true) {
            $out[] = $row->item_quantity ?? '';
        }
        if ($vis['tailor'] ?? true) {
            $out[] = $row->tailor_name ?? '';
        }
        if ($vis['tailor_commission'] ?? true) {
            $out[] = $row->tailor_commission ?? '';
        }
        if ($vis['completion_date'] ?? true) {
            $out[] = $row->completion_date ? systemDate($row->completion_date) : '';
        }
        if ($vis['rating'] ?? true) {
            $out[] = $row->rating ?? '';
        }
        if ($vis['status'] ?? true) {
            $out[] = ucfirst($row->status ?? '');
        }
        if ($vis['created_at'] ?? true) {
            $out[] = $row->created_at ? systemDateTime($row->created_at) : '';
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
            },
        ];
    }
}
