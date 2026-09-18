<?php

namespace App\Actions\V1\DaySession;

use App\Actions\Sale\BuildDaySessionReportAction;
use App\Models\SaleDaySession;
use App\Traits\UsesBrowsershot;

/**
 * The web "Sale Bill Report" for one day session (print::sale::day-session-report)
 * handed to the mobile app two ways:
 *
 *  - as data, which the app lays out on the thermal roll itself — like its
 *    receipts, so Arabic shapes and a paired printer takes it with no dialog;
 *  - as the A4 PDF — the web's own `sale.day-session-print-pdf` view rendered
 *    through Chrome, so the phone's copy is the document the back office prints.
 *
 * Both read BuildDaySessionReportAction::payload(), the one place the report's
 * figures are worked out.
 */
class ReportAction
{
    use UsesBrowsershot;

    public function __construct(private BuildDaySessionReportAction $report) {}

    /**
     * Found through the model's AssignedBranchScope and tenant scope, so a
     * session from a branch the caller isn't assigned to reads as missing.
     */
    public function find(int $id): SaleDaySession
    {
        return SaleDaySession::with(['branch', 'opener:id,name', 'closer:id,name'])->findOrFail($id);
    }

    /**
     * One session as a list row / report header. Times are the wall clock the
     * web report prints, not a UTC instant.
     *
     * @return array<string, mixed>
     */
    public function row(SaleDaySession $session): array
    {
        return [
            'id' => (string) $session->id,
            'branch' => $session->branch?->name,
            'branch_location' => $session->branch?->location,
            'branch_mobile' => $session->branch?->mobile,
            'status' => $session->status,
            'opened_at' => $session->opened_at?->format('Y-m-d H:i:s'),
            'closed_at' => $session->closed_at?->format('Y-m-d H:i:s'),
            'opened_by' => $session->opened_by_name,
            'closed_by' => $session->closed_by_name,
            // So the app can pick out the signed-in person by id, not by name.
            'opened_by_id' => $session->opened_by ? (string) $session->opened_by : null,
            'closed_by_id' => $session->closed_by ? (string) $session->closed_by : null,
        ];
    }

    /**
     * Everything the thermal report prints.
     *
     * @return array<string, mixed>
     */
    public function data(SaleDaySession $session): array
    {
        $payload = $this->report->payload($session);
        $totals = array_map('floatval', $payload['totals']);

        return [
            'session' => $this->row($session),
            'transactions' => collect($payload['transactions'])->map(fn (array $row) => [
                'source' => $row['source'],
                'reference_no' => $row['reference_no'],
                'amount' => (float) $row['amount'],
                'paid_amount' => (float) $row['paid_amount'],
                'due_amount' => (float) $row['due_amount'],
                'payments' => array_map(fn (array $payment) => [
                    'method' => $payment['method'],
                    'amount' => (float) $payment['amount'],
                ], $row['payment_rows'] ?? []),
            ])->values()->all(),
            'due_transactions' => collect($payload['dueTransactions'])->map(fn (array $row) => [
                'source' => $row['source'],
                'reference_no' => $row['reference_no'],
                'due_amount' => (float) $row['due_amount'],
            ])->values()->all(),
            'due_payments' => collect($payload['pendingPayments'])->map(fn (array $row) => [
                'source' => $row['source'],
                'reference_no' => $row['reference_no'],
                'payment_method' => $row['payment_method'],
                'amount' => (float) $row['amount'],
            ])->values()->all(),
            'totals' => array_merge($totals, [
                // The three the web views work out inline.
                'card_with_due' => $totals['card'] + $totals['due_total_card'],
                'cash_with_due' => $totals['cash'] + $totals['due_total_cash'],
                'grand_total_payment' => $totals['payment_total'] + $totals['due_total'],
            ]),
        ];
    }

    /**
     * The web A4 report as PDF bytes.
     */
    public function pdf(SaleDaySession $session): string
    {
        $html = $this->report->executePdf($session)->render();

        // The view declares `@page { size: A4; margin: 14mm }`; the same margins
        // here keep the page identical whichever of the two Chrome honours.
        return $this->makeBrowsershot($html)
            ->format('A4')
            ->margins(14, 14, 14, 14)
            ->showBackground()
            ->pdf();
    }
}
