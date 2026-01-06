<?php

namespace App\Actions\Account\Customer;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class GenerateStatementAction
{
    protected $customerId;

    protected $fromDate;

    protected $toDate;

    protected $customer;

    public function execute($customerId, $fromDate = null, $toDate = null)
    {
        $this->customerId = $customerId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;

        $this->customer = $this->getCustomer();
        $openingBalance = $this->calculateOpeningBalance();
        $ledgerEntries = $this->buildLedgerEntries($openingBalance);
        $ledgerEntries = $this->sortAndCalculateBalances($ledgerEntries);
        $totals = $this->calculateTotals($ledgerEntries, $openingBalance);
        $companyInfo = $this->getCompanyInfo();

        return $this->generatePdf($ledgerEntries, $totals, $companyInfo);
    }

    protected function getCustomer(): Account
    {
        return Account::where(function ($query) {
            $query->where('model', 'customer')->orWhere('model', 'Customer');
        })
            ->where('account_type', 'asset')
            ->with('customerType')
            ->findOrFail($this->customerId);
    }

    protected function getSales()
    {
        $query = Sale::where('account_id', $this->customerId)
            ->where('status', 'completed')
            ->with(['payments' => function ($q) {
                $q->orderBy('date', 'asc')->orderBy('id', 'asc');
            }, 'payments.paymentMethod', 'branch']);

        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('date', '<=', $this->toDate);
        }

        return $query->orderBy('date', 'asc')->orderBy('invoice_no', 'asc')->get();
    }

    protected function getSaleReturns()
    {
        $query = SaleReturn::where('account_id', $this->customerId)
            ->where('status', 'completed')
            ->with(['payments' => function ($q) {
                $q->orderBy('date', 'asc')->orderBy('id', 'asc');
            }, 'payments.paymentMethod', 'branch']);

        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('date', '<=', $this->toDate);
        }

        return $query->orderBy('date', 'asc')->orderBy('reference_no', 'asc')->get();
    }

    protected function calculateOpeningBalance(): float
    {
        if (! $this->fromDate) {
            return 0;
        }

        $openingSales = Sale::where('account_id', $this->customerId)
            ->where('status', 'completed')
            ->whereDate('date', '<', $this->fromDate)
            ->sum('grand_total');

        $openingSaleReturns = SaleReturn::where('account_id', $this->customerId)
            ->where('status', 'completed')
            ->whereDate('date', '<', $this->fromDate)
            ->sum('grand_total');

        $openingPayments = SalePayment::whereHas('sale', function ($q) {
            $q->where('account_id', $this->customerId)
                ->where('status', 'completed')
                ->whereDate('date', '<', $this->fromDate);
        })->sum('amount');

        $openingReturnPayments = SaleReturnPayment::whereHas('saleReturn', function ($q) {
            $q->where('account_id', $this->customerId)
                ->where('status', 'completed')
                ->whereDate('date', '<', $this->fromDate);
        })->sum('amount');

        return $openingSales - $openingSaleReturns - ($openingPayments - $openingReturnPayments);
    }

    protected function buildLedgerEntries($openingBalance = 0): Collection
    {
        $ledgerEntries = collect();

        // Add opening balance as the first entry if it's not zero
        if ($openingBalance != 0) {
            $openingEntry = $this->createOpeningBalanceEntry($openingBalance);
            $ledgerEntries->push($openingEntry);
        }

        $sales = $this->getSales();
        $saleReturns = $this->getSaleReturns();

        // Process sales with their payments grouped together
        foreach ($sales as $sale) {
            $saleEntry = $this->createSaleEntry($sale);
            $ledgerEntries->push($saleEntry);

            // Add payments immediately after the sale to keep them together
            foreach ($sale->payments as $payment) {
                $paymentEntry = $this->createPaymentEntry($payment, $sale->invoice_no, $sale->date);
                $ledgerEntries->push($paymentEntry);
            }
        }

        // Process sale returns with their payments grouped together
        foreach ($saleReturns as $saleReturn) {
            $returnEntry = $this->createSaleReturnEntry($saleReturn);
            $ledgerEntries->push($returnEntry);

            // Add return payments immediately after the return to keep them together
            foreach ($saleReturn->payments as $payment) {
                if ($payment->amount != 0) {
                    $returnPaymentEntry = $this->createReturnPaymentEntry($payment, $saleReturn);
                    $ledgerEntries->push($returnPaymentEntry);
                }
            }
        }

        return $ledgerEntries;
    }

    protected function createOpeningBalanceEntry($openingBalance): object
    {
        $openingDate = $this->fromDate ? date('Y-m-d', strtotime($this->fromDate.' -1 day')) : '1900-01-01';

        return (object) [
            'date' => 'Opening',
            'sort_date' => $openingDate, // Sort before all other entries
            'reference' => 'Opening Balance',
            'debit' => $openingBalance > 0 ? abs($openingBalance) : 0,
            'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
            'type' => 'opening_balance',
            'group_key' => 'opening_balance', // Unique group key
            'sort_order' => 0, // Comes first
            'model' => null,
        ];
    }

    protected function createSaleEntry($sale): object
    {
        return (object) [
            'date' => $sale->date,
            'sort_date' => $sale->date, // Same as date for sales
            'reference' => $sale->invoice_no,
            'debit' => $sale->grand_total,
            'credit' => 0,
            'type' => 'sale',
            'group_key' => 'sale_'.$sale->id, // Group key to keep related entries together
            'sort_order' => 1, // Sale comes first in group
            'model' => $sale,
        ];
    }

    protected function createPaymentEntry($payment, $invoiceNo, $saleDate = null): object
    {
        $saleId = $payment->sale_id ?? $payment->sale->id ?? null;
        // Use sale date for sorting to keep payment near sale, but display actual payment date
        $sortDate = $saleDate ?? $payment->date;

        return (object) [
            'date' => $payment->date, // Display date (actual payment date)
            'sort_date' => $sortDate, // Sort date (sale date to keep together)
            'reference' => $invoiceNo,
            'debit' => 0,
            'credit' => $payment->amount,
            'type' => 'payment',
            'group_key' => 'sale_'.$saleId, // Same group as parent sale
            'sort_order' => 2, // Payment comes after sale
            'model' => $payment,
        ];
    }

    protected function createSaleReturnEntry($saleReturn): object
    {
        $reference = $saleReturn->reference_no ?: 'SR-'.$saleReturn->id;

        return (object) [
            'date' => $saleReturn->date,
            'sort_date' => $saleReturn->date, // Same as date for returns
            'reference' => $reference,
            'debit' => 0,
            'credit' => $saleReturn->grand_total,
            'type' => 'sale_return',
            'group_key' => 'return_'.$saleReturn->id, // Group key to keep related entries together
            'sort_order' => 1, // Return comes first in group
            'model' => $saleReturn,
        ];
    }

    protected function createReturnPaymentEntry($payment, $saleReturn): object
    {
        $reference = $saleReturn->reference_no ?: 'SR-'.$saleReturn->id;
        $returnId = $payment->sale_return_id ?? $payment->saleReturn->id ?? $saleReturn->id;
        // Use return date for sorting to keep payment near return, but display actual payment date
        $sortDate = $saleReturn->date;

        return (object) [
            'date' => $payment->date, // Display date (actual payment date)
            'sort_date' => $sortDate, // Sort date (return date to keep together)
            'reference' => $reference,
            'debit' => $payment->amount,
            'credit' => 0,
            'type' => 'return_payment',
            'group_key' => 'return_'.$returnId, // Same group as parent return
            'sort_order' => 2, // Payment comes after return
            'model' => $payment,
        ];
    }

    protected function sortAndCalculateBalances($ledgerEntries): Collection
    {
        // Sort by sort_date (or date if sort_date not set), then by group_key (to keep related entries together), then by sort_order
        $sortedEntries = $ledgerEntries->sortBy(function ($entry) {
            $sortDate = $entry->sort_date ?? $entry->date;

            return $sortDate.'_'.$entry->group_key.'_'.str_pad($entry->sort_order, 2, '0', STR_PAD_LEFT);
        })->values();

        // Calculate running balance starting from 0
        // The opening balance entry (if present) will be first and will set the initial balance
        $runningBalance = 0;
        foreach ($sortedEntries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->balance = $runningBalance;
        }

        return $sortedEntries;
    }

    protected function calculateTotals($ledgerEntries, $openingBalance): array
    {
        return [
            'totalDebit' => $ledgerEntries->sum('debit'),
            'totalCredit' => $ledgerEntries->sum('credit'),
            'openingBalance' => $openingBalance,
            'closingBalance' => $ledgerEntries->last()->balance ?? $openingBalance,
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
            'customer' => $this->customer,
            'ledgerEntries' => $ledgerEntries,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ], $totals, $companyInfo);

        $html = view('accounts.customer_statement', $data)->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        $filename = 'customer_statement_'.$this->customer->name.'_'.now()->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }
}
