<?php

namespace App\Exports;

use App\Traits\BuildsCustomerReminderQuery;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerReminderCallbackExport implements FromQuery, WithChunkReading, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    use BuildsCustomerReminderQuery;

    private array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return $this->buildCustomerReminderListQuery($this->filters);
    }

    public function map($account): array // $account is an object from the query
    {
        $daysSinceLastPurchase = null;
        if ($account->last_purchase_date) {
            $daysSinceLastPurchase = Carbon::parse($account->last_purchase_date)->diffInDays(now(), true);
        }

        $priority = $this->getPriorityLabel($daysSinceLastPurchase);

        return [
            'ID' => $account->id,
            'Name' => $account->name,
            'Mobile' => $account->mobile ?? 'N/A',
            'Email' => $account->email ?? 'N/A',
            'Nationality' => $account->nationality ?? 'N/A',
            'Last Purchase Date' => $account->last_purchase_date ? systemDate($account->last_purchase_date) : 'N/A',
            'Days Since Last Purchase' => $daysSinceLastPurchase !== null ? abs(round($daysSinceLastPurchase)) : 'N/A',
            'Total Purchases' => $account->total_purchases ?? 0,
            'Total Spent' => $account->total_spent ?? 0,
            'Priority' => $priority,
            'Customer Since' => $account->created_at ? systemDateTime($account->created_at) : 'N/A',
        ];
    }

    private function getPriorityLabel(?int $days): string
    {
        if ($days === null) {
            return 'Unknown';
        }

        return match (true) {
            $days > 90 => 'High Priority',
            $days > 60 => 'Medium Priority',
            $days > 30 => 'Low Priority',
            $days >= 0 && $days <= 30 => 'Recent',
            default => 'Unknown',
        };
    }

    public function headings(): array
    {
        return [
            'Customer ID',
            'Customer Name',
            'Mobile Number',
            'Email Address',
            'Nationality',
            'Last Purchase Date',
            'Days Since Last Purchase',
            'Total Purchases',
            'Total Amount Spent',
            'Priority Level',
            'Customer Since',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 25,
            'C' => 15,
            'D' => 30,
            'E' => 15,
            'F' => 18,
            'G' => 20,
            'H' => 15,
            'I' => 18,
            'J' => 15,
            'K' => 18,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
