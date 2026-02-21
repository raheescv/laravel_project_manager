<?php

namespace App\Exports;

use App\Traits\Report\BuildsTailoringNonDeliveryQuery;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class TailoringNonDeliveryReportExport implements FromQuery, WithEvents, WithHeadings, WithMapping
{
    use BuildsTailoringNonDeliveryQuery;
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $allowedBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();
        return $this->nonDeliveryRowsQuery($this->filters, $allowedBranchIds)
            ->orderBy('tailoring_orders.order_date', 'desc');
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
        if ($vis['delivery_date'] ?? true) {
            $out[] = 'Delivery Date';
        }
        if ($vis['customer'] ?? true) {
            $out[] = 'Customer';
        }
        if ($vis['mobile'] ?? true) {
            $out[] = 'Mobile';
        }
        if ($vis['bill_amount'] ?? true) {
            $out[] = 'Bill Amount';
        }
        if ($vis['paid_amount'] ?? true) {
            $out[] = 'Paid';
        }
        if ($vis['balance_amount'] ?? true) {
            $out[] = 'Balance';
        }
        if ($vis['item_quantity'] ?? true) {
            $out[] = 'Item Qty';
        }
        if ($vis['completed_qty'] ?? true) {
            $out[] = 'Completed Qty';
        }
        if ($vis['pending_qty'] ?? true) {
            $out[] = 'Pending Qty';
        }
        if ($vis['delivery_qty'] ?? true) {
            $out[] = 'Delivery Qty';
        }
        if ($vis['order_status'] ?? true) {
            $out[] = 'Order Status';
        }

        return $out;
    }

    public function map($row): array
    {
        $vis = $this->visibleColumns();
        $statusLabel = tailoringOrderStatuses()[$row->order_status] ?? ucfirst((string) $row->order_status);
        $out = [$row->id];

        if ($vis['order_no'] ?? true) {
            $out[] = $row->order_no;
        }
        if ($vis['order_date'] ?? true) {
            $out[] = $row->order_date ? systemDate($row->order_date) : '';
        }
        if ($vis['delivery_date'] ?? true) {
            $out[] = $row->delivery_date ? systemDate($row->delivery_date) : '';
        }
        if ($vis['customer'] ?? true) {
            $out[] = $row->customer_name ?? '';
        }
        if ($vis['mobile'] ?? true) {
            $out[] = $row->customer_mobile ?? '';
        }
        if ($vis['bill_amount'] ?? true) {
            $out[] = $row->bill_amount ?? 0;
        }
        if ($vis['paid_amount'] ?? true) {
            $out[] = $row->paid_amount ?? 0;
        }
        if ($vis['balance_amount'] ?? true) {
            $out[] = $row->balance_amount ?? 0;
        }
        if ($vis['item_quantity'] ?? true) {
            $out[] = $row->item_quantity ?? 0;
        }
        if ($vis['completed_qty'] ?? true) {
            $out[] = $row->completed_qty ?? 0;
        }
        if ($vis['pending_qty'] ?? true) {
            $out[] = $row->pending_qty ?? 0;
        }
        if ($vis['delivery_qty'] ?? true) {
            $out[] = $row->delivery_qty ?? 0;
        }
        if ($vis['order_status'] ?? true) {
            $out[] = $statusLabel;
        }

        return $out;
    }

    protected function visibleColumns(): array
    {
        $defaults = [
            'order_no' => true,
            'order_date' => true,
            'delivery_date' => true,
            'customer' => true,
            'mobile' => true,
            'bill_amount' => true,
            'paid_amount' => true,
            'balance_amount' => true,
            'item_quantity' => true,
            'completed_qty' => true,
            'pending_qty' => true,
            'delivery_qty' => true,
            'order_status' => true,
        ];

        return array_merge($defaults, (array) ($this->filters['visible_columns'] ?? []));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }
}
