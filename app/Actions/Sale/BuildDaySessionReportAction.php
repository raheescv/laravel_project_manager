<?php

namespace App\Actions\Sale;

use App\Models\SaleDaySession;
use App\Services\SaleDaySessionDataService;

class BuildDaySessionReportAction
{
    public function execute(SaleDaySession $session)
    {
        return view('sale.day-session-print', $this->buildPayload($session));
    }

    public function executePdf(SaleDaySession $session)
    {
        return view('sale.day-session-print-pdf', $this->buildPayload($session));
    }

    private function buildPayload(SaleDaySession $session): array
    {
        $sessionDataService = app(SaleDaySessionDataService::class);
        $sessionDate = date('Y-m-d', strtotime($session->opened_at));

        $sales = $this->loadSessionSales($sessionDataService, $session->id, $sessionDate);
        $tailoringOrders = $this->loadSessionTailoringOrders($sessionDataService, $session->id, $sessionDate);

        $transactions = $this
            ->buildTransactions($sales, 'Sale', 'date', 'invoice_no')
            ->merge($this->buildTransactions($tailoringOrders, 'Tailoring', 'order_date', 'order_no'))
            ->sortBy(function (array $row) {
                return sprintf('%s|%s', $row['date'], $row['reference_no'] ?? '');
            })->values();

        $dueTransactions = $transactions
            ->filter(fn (array $row) => (float) ($row['due_amount'] ?? 0) > 0)
            ->values();

        $totals = $this->initialTotals();
        $totals['sale_tailoring_amount'] = (float) $transactions->sum('amount');
        $totals['payment_total'] = (float) $transactions->sum('paid_amount');
        $totals['due_total'] = (float) $dueTransactions->sum('due_amount');
        $totals['credit'] = $totals['due_total'];

        $this->applyPaymentMethodTotals($sales, $totals);
        $this->applyPaymentMethodTotals($tailoringOrders, $totals);

        $salePayments = $sessionDataService
            ->salePaymentsQueryForSessionOpenDateBranch($session)
            ->with(['sale:id,invoice_no,sale_day_session_id', 'paymentMethod:id,name'])
            ->get();

        $tailoringPayments = $sessionDataService
            ->tailoringPaymentsQueryForSessionOpenDateBranch($session)
            ->with(['order:id,order_no,sale_day_session_id', 'paymentMethod:id,name'])
            ->get();

        $pendingPayments = collect()
            ->merge($this->collectPendingSalePayments($salePayments, $session->id, $totals))
            ->merge($this->collectPendingTailoringPayments($tailoringPayments, $session->id, $totals))
            ->values()
            ->all();

        $totals['due_total'] = (float) $totals['due_total_cash'] + (float) $totals['due_total_card'];

        return compact('session', 'pendingPayments', 'transactions', 'dueTransactions', 'totals');
    }

    private function loadSessionSales(SaleDaySessionDataService $service, int $sessionId, string $sessionDate)
    {
        return $service
            ->salesQueryForSession($sessionId, true)
            ->with([
                'branch',
                'payments' => fn ($query) => $query->whereDate('date', $sessionDate)->with('paymentMethod'),
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function loadSessionTailoringOrders(SaleDaySessionDataService $service, int $sessionId, string $sessionDate)
    {
        return $service
            ->tailoringOrdersQueryForSession($sessionId, true)
            ->with([
                'branch',
                'payments' => fn ($query) => $query->whereDate('date', $sessionDate)->with('paymentMethod'),
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function buildTransactions($rows, string $source, string $dateField, string $referenceField)
    {
        return collect($rows)->map(function ($row) use ($source, $dateField, $referenceField) {
            $amount = (float) ($row->grand_total ?? 0);
            $paidAmount = (float) $row->payments->sum(fn ($payment) => (float) ($payment->amount ?? 0));

            return [
                'source' => $source,
                'date' => $row->{$dateField},
                'reference_no' => $row->{$referenceField},
                'amount' => $amount,
                'paid_amount' => $paidAmount,
                'due_amount' => max($amount - $paidAmount, 0),
                'payment_rows' => $this->paymentRowsFromCollection($row->payments),
            ];
        });
    }

    private function paymentRowsFromCollection($payments): array
    {
        return collect($payments)
            ->filter(fn ($payment) => (float) ($payment->amount ?? 0) > 0)
            ->groupBy(fn ($payment) => strtolower(trim((string) ($payment->paymentMethod?->name ?: 'Payment'))))
            ->map(function ($group, $key) {
                $first = $group->first();

                return [
                    'method' => $first->paymentMethod?->name ?: ucfirst($key),
                    'amount' => (float) $group->sum(fn ($payment) => (float) ($payment->amount ?? 0)),
                ];
            })
            ->values()
            ->toArray();
    }

    private function initialTotals(): array
    {
        return [
            'credit' => 0,
            'cash' => 0,
            'card' => 0,
            'payment_cash' => 0,
            'due_total_cash' => 0,
            'due_total_card' => 0,
            'sale_tailoring_amount' => 0,
            'payment_total' => 0,
            'due_total' => 0,
        ];
    }

    private function applyPaymentMethodTotals($rows, array &$totals): void
    {
        foreach ($rows as $row) {
            foreach ($row->payments as $payment) {
                $amount = (float) ($payment->amount ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $key = $this->normalizeMethod($payment->paymentMethod?->name);
                if (! isset($totals[$key])) {
                    $totals[$key] = 0;
                }
                $totals[$key] += $amount;
            }
        }
    }

    private function collectPendingSalePayments($payments, int $sessionId, array &$totals): array
    {
        $pendingPayments = [];

        foreach ($payments as $payment) {
            if ($payment->sale->sale_day_session_id == $sessionId) {
                continue;
            }

            $pendingPayments[] = [
                'source' => 'Sale',
                'date' => $payment->date,
                'reference_no' => $payment->sale->invoice_no,
                'payment_method' => $payment->name,
                'amount' => $payment->amount,
            ];

            $this->applyDuePaymentMethodTotal($payment->name, (float) $payment->amount, $totals);
        }

        return $pendingPayments;
    }

    private function collectPendingTailoringPayments($payments, int $sessionId, array &$totals): array
    {
        $pendingPayments = [];

        foreach ($payments as $payment) {
            if ($payment->order && $payment->order->sale_day_session_id == $sessionId) {
                continue;
            }

            $pendingPayments[] = [
                'source' => 'Tailoring',
                'date' => $payment->date,
                'reference_no' => $payment->order?->order_no,
                'payment_method' => $payment->name,
                'amount' => $payment->amount,
            ];

            $this->applyDuePaymentMethodTotal($payment->name, (float) $payment->amount, $totals);
        }

        return $pendingPayments;
    }

    private function applyDuePaymentMethodTotal(?string $methodName, float $amount, array &$totals): void
    {
        $normalizedMethod = $this->normalizeMethod($methodName);
        if ($normalizedMethod === 'cash') {
            $totals['payment_cash'] += $amount;
            $totals['due_total_cash'] += $amount;
        } elseif ($normalizedMethod === 'card') {
            $totals['due_total_card'] += $amount;
        }
    }

    private function normalizeMethod(?string $name): string
    {
        $method = strtolower(trim((string) $name));
        if ($method === '') {
            return 'other';
        }
        if (str_contains($method, 'cash')) {
            return 'cash';
        }
        if (str_contains($method, 'card')) {
            return 'card';
        }
        if (str_contains($method, 'credit')) {
            return 'credit';
        }

        return $method;
    }
}
