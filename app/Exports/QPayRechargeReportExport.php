<?php

namespace App\Exports;

use App\Livewire\Report\Student\QPayRechargeReport;
use App\Models\QpayTransaction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** The QPay recharge report as filtered on screen (QPayRechargeReport::filteredQuery). */
class QPayRechargeReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return QPayRechargeReport::filteredQuery($this->filters);
    }

    public function headings(): array
    {
        return ['Date', 'PUN', 'Type', 'Student', 'Admission No', 'Class', 'Parent', 'Parent Mobile', 'Card', 'Amount', 'Status', 'QPay Code', 'QPay Message', 'Confirmation'];
    }

    public function map($row): array
    {
        return [
            $row->created_at?->format('Y-m-d H:i'),
            $row->pun,
            ucfirst($row->type),
            $row->student_name,
            $row->admission_no,
            trim(implode(' - ', array_filter([$row->grade, $row->section]))),
            $row->guardian_name,
            $row->guardian_mobile,
            $row->masked_card,
            round((float) $row->amount * ($row->type === QpayTransaction::TYPE_REFUND ? -1 : 1), 2),
            $row->statusLabel(),
            $row->gateway_status,
            $row->gateway_status_message,
            $row->confirmation_id,
        ];
    }
}
