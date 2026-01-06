<?php

namespace App\Actions\Package;

use App\Models\Configuration;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class GeneratePackageStatementAction
{
    protected $packageId;

    protected $fromDate;

    protected $toDate;

    protected $package;

    public function execute($packageId, $fromDate = null, $toDate = null)
    {
        $this->packageId = $packageId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;

        $this->package = $this->getPackage();
        $ledgerEntries = $this->buildLedgerEntries();
        $ledgerEntries = $this->sortAndCalculateBalances($ledgerEntries);
        $totals = $this->calculateTotals($ledgerEntries);
        $companyInfo = $this->getCompanyInfo();

        return $this->generatePdf($ledgerEntries, $totals, $companyInfo);
    }

    protected function getPackage(): Package
    {
        return Package::with(['packageCategory', 'account', 'items', 'payments.paymentMethod'])->findOrFail($this->packageId);
    }

    protected function buildLedgerEntries(): Collection
    {
        $ledgerEntries = collect();

        // Add package opening entry (package creation)
        $packageEntry = $this->createPackageEntry();
        $ledgerEntries->push($packageEntry);

        // Add visit entries (package items)
        $visits = $this->getVisits();
        foreach ($visits as $visit) {
            $visitEntry = $this->createVisitEntry($visit);
            $ledgerEntries->push($visitEntry);
        }
        // Add payment entries
        $payments = $this->getPayments();
        foreach ($payments as $payment) {
            $paymentEntry = $this->createPaymentEntry($payment);
            $ledgerEntries->push($paymentEntry);
        }

        return $ledgerEntries;
    }

    protected function createPackageEntry(): object
    {
        return (object) [
            'date' => $this->package->start_date,
            'sort_date' => $this->package->start_date,
            'reference' => 'Package #'.$this->package->id,
            'description' => 'Package: '.($this->package->packageCategory->name ?? 'N/A'),
            'debit' => $this->package->amount,
            'credit' => 0,
            'type' => 'package',
            'sort_order' => 1,
            'model' => $this->package,
        ];
    }

    protected function createVisitEntry($visit): object
    {
        $statusLabels = [
            'visited' => 'Visited',
            'rescheduled' => 'Rescheduled',
            'pending' => 'Pending',
        ];

        $displayDate = $visit->rescheduled_date ? $visit->rescheduled_date : $visit->date;
        $description = 'Visit - '.($statusLabels[$visit->status] ?? $visit->status);
        if ($visit->rescheduled_date) {
            $description .= ' (Rescheduled from '.date('d-m-Y', strtotime($visit->date)).')';
        }
        if ($visit->notes) {
            $description .= ' - '.$visit->notes;
        }

        return (object) [
            'date' => $displayDate,
            'sort_date' => $visit->date,
            'reference' => 'Visit #'.$visit->id,
            'description' => $description,
            'debit' => 0,
            'credit' => 0,
            'type' => 'visit',
            'sort_order' => 2,
            'model' => $visit,
        ];
    }

    protected function createPaymentEntry($payment): object
    {
        $paymentMethodName = $payment->paymentMethod ? $payment->paymentMethod->name : 'N/A';

        return (object) [
            'date' => $payment->date,
            'sort_date' => $payment->date,
            'reference' => 'Payment #'.$payment->id,
            'description' => 'Payment via '.$paymentMethodName,
            'debit' => 0,
            'credit' => $payment->amount,
            'type' => 'payment',
            'sort_order' => 3,
            'model' => $payment,
        ];
    }

    protected function getVisits()
    {
        $query = PackageItem::where('package_id', $this->packageId)->orderBy('date', 'asc');

        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('date', '<=', $this->toDate);
        }

        return $query->get();
    }

    protected function getPayments()
    {
        $query = PackagePayment::where('package_id', $this->packageId)
            ->with('paymentMethod')
            ->orderBy('date', 'asc');

        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('date', '<=', $this->toDate);
        }

        return $query->get();
    }

    protected function sortAndCalculateBalances($ledgerEntries): Collection
    {
        // Sort by sort_date, then by sort_order
        $sortedEntries = $ledgerEntries->sortBy(function ($entry) {
            return $entry->sort_date.'_'.str_pad($entry->sort_order, 2, '0', STR_PAD_LEFT);
        })->values();

        // Calculate running balance
        $runningBalance = 0;
        foreach ($sortedEntries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->balance = $runningBalance;
        }

        return $sortedEntries;
    }

    protected function calculateTotals($ledgerEntries): array
    {
        return [
            'totalDebit' => $ledgerEntries->sum('debit'),
            'totalCredit' => $ledgerEntries->sum('credit'),
            'openingBalance' => 0,
            'closingBalance' => $ledgerEntries->last()->balance ?? 0,
            'packageAmount' => $this->package->amount,
            'totalPaid' => $this->package->paid,
            'currentBalance' => $this->package->balance,
        ];
    }

    protected function getCompanyInfo(): array
    {
        return [
            'companyName' => Configuration::where('key', 'company_name')->value('value') ?? config('app.name'),
            'companyAddress' => Configuration::where('key', 'company_address')->value('value') ?? '',
            'companyPhone' => Configuration::where('key', 'company_phone')->value('value') ?? '',
            'companyEmail' => Configuration::where('key', 'company_email')->value('value') ?? '',
        ];
    }

    protected function generatePdf($ledgerEntries, $totals, $companyInfo)
    {
        $data = array_merge([
            'package' => $this->package,
            'ledgerEntries' => $ledgerEntries,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ], $totals, $companyInfo);

        $html = view('package.statement', $data)->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        $customerName = $this->package->account ? str_replace(' ', '_', $this->package->account->name) : 'Customer';
        $filename = 'package_statement_'.$customerName.'_#'.$this->package->id.'_'.now()->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }
}
