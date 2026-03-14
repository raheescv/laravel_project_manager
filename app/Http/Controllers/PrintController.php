<?php

namespace App\Http\Controllers;

use App\Actions\RentOut\GenerateReservationFormAction;
use App\Actions\RentOut\GenerateResidentialLeaseAction;
use App\Helpers\Facades\SaleHelper;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Models\RentOutPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function saleInvoice($id)
    {
        return SaleHelper::saleInvoice($id);
    }

    public function daySessionReport($id)
    {
        return SaleHelper::daySessionReport($id);
    }

    public function daySessionReportPdf($id)
    {
        return SaleHelper::daySessionReportPdf($id);
    }

    public function customerReceipt(Request $request)
    {
        $data = $this->getPaymentReceiptViewData($request, [
            'receiptTitle' => 'PAYMENT RECEIPT',
            'referenceColumnLabel' => 'Invoice No',
            'referenceKey' => 'invoice_no',
            'footerMessage' => 'THANK YOU FOR YOUR PAYMENT',
            'pageTitle' => 'Customer Receipt',
        ]);

        return view('print.sale.receipt', $data);
    }

    public function saleReturnPaymentReceipt(Request $request)
    {
        $data = $this->getPaymentReceiptViewData($request, [
            'receiptTitle' => 'SALE RETURN PAYMENT RECEIPT',
            'referenceColumnLabel' => 'Reference No',
            'referenceKey' => 'reference_no',
            'footerMessage' => 'THANK YOU FOR VISITING US',
            'pageTitle' => 'Sale Return Payment Receipt',
        ]);

        return view('print.sale.receipt', $data);
    }

    public function tailoringCustomerReceipt(Request $request)
    {
        $enable_logo_in_print = Configuration::where('key', 'enable_logo_in_print')->value('value');
        $data = $this->getPaymentReceiptViewData($request, [
            'receiptTitle' => 'TAILORING PAYMENT RECEIPT',
            'referenceColumnLabel' => 'Order No',
            'referenceKey' => 'invoice_no',
            'footerMessage' => 'THANK YOU FOR YOUR PAYMENT',
            'enable_logo_in_print' => $enable_logo_in_print,
            'pageTitle' => 'Tailoring Customer Receipt',
        ]);

        return view('print.tailoring.customer-receipt-thermal', $data);
    }

    public function rentoutStatement($id, $fromDate = null, $toDate = null)
    {
        $rentOut = RentOut::with([
            'customer', 'property', 'building', 'group', 'type',
            'paymentTerms', 'journals',
        ])->findOrFail($id);

        $payments = collect();

        // Add payment term debits
        foreach ($rentOut->paymentTerms as $term) {
            $payments->push([
                'date' => $term->due_date,
                'payment_mode' => 'Rent Due',
                'cheque_no' => '',
                'debit' => $term->total ?? 0,
                'credit' => 0,
                'remark' => $term->remarks ?? '',
            ]);
        }

        // Add journal credits (actual payments)
        foreach ($rentOut->journals as $journal) {
            if (($journal->credit ?? 0) > 0) {
                $payments->push([
                    'date' => $journal->date,
                    'payment_mode' => $journal->payment_mode ?? '',
                    'cheque_no' => $journal->cheque_no ?? '',
                    'debit' => 0,
                    'credit' => $journal->credit ?? 0,
                    'remark' => $journal->remark ?? '',
                ]);
            }
        }

        // Filter by date range
        if ($fromDate && $toDate) {
            $payments = $payments->filter(function ($payment) use ($fromDate, $toDate) {
                $date = Carbon::parse($payment['date']);

                return $date->greaterThanOrEqualTo($fromDate) && $date->lessThanOrEqualTo($toDate);
            });
        }

        $payments = $payments->sortBy('date')->values();

        $data = array_merge(
            compact('rentOut', 'payments', 'fromDate', 'toDate'),
            $this->getCompanyInfo()
        );

        $pdf = Pdf::loadView('print.rentout.statement', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('rentout_statement.pdf');
    }

    public function rentoutUtilitiesStatement($id, $fromDate = null, $toDate = null)
    {
        $rentOut = RentOut::with([
            'customer', 'property', 'building', 'group', 'type',
            'utilityTerms.utility', 'journals',
        ])->findOrFail($id);

        $payments = collect();

        // Add utility term debits
        $utilityTerms = $rentOut->utilityTerms;
        if ($fromDate && $toDate) {
            $utilityTerms = $utilityTerms->whereBetween('date', [$fromDate, $toDate]);
        }

        foreach ($utilityTerms as $uTerm) {
            $payments->push([
                'date' => $uTerm->date,
                'utility' => $uTerm->utility?->name ?? '',
                'payment_mode' => 'Utility Due',
                'debit' => $uTerm->amount ?? 0,
                'credit' => 0,
                'remark' => $uTerm->remarks ?? '',
            ]);
        }

        // Add utility payment credits from journals
        $journals = $rentOut->journals;
        if ($fromDate && $toDate) {
            $journals = $journals->filter(fn ($j) => Carbon::parse($j->date)->between($fromDate, $toDate));
        }

        foreach ($journals as $journal) {
            if (($journal->credit ?? 0) > 0 && str_contains(strtolower($journal->category ?? ''), 'utility')) {
                $payments->push([
                    'date' => $journal->date,
                    'utility' => $journal->category ?? '',
                    'payment_mode' => $journal->payment_mode ?? '',
                    'debit' => 0,
                    'credit' => $journal->credit ?? 0,
                    'remark' => $journal->remark ?? '',
                ]);
            }
        }

        $payments = $payments->sortBy('date')->values();

        $data = array_merge(
            compact('rentOut', 'payments', 'fromDate', 'toDate'),
            $this->getCompanyInfo()
        );

        $pdf = Pdf::loadView('print.rentout.utilities-statement', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('rentout_utilities_statement.pdf');
    }

    public function rentOutPaymentReceipt($id)
    {
        $payment = RentOutPayment::with(['account', 'rentOut.customer', 'rentOut.property', 'rentOut.building'])->findOrFail($id);
        $rentOut = $payment->rentOut;

        $data = array_merge(
            compact('payment', 'rentOut'),
            $this->getCompanyInfo()
        );

        $pdf = Pdf::loadView('print.rentout.receipt', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("receipt_{$payment->id}.pdf");
    }

    public function rentOutPaymentVoucher($id)
    {
        $payment = RentOutPayment::with(['account', 'rentOut.customer', 'rentOut.property', 'rentOut.building'])->findOrFail($id);
        $rentOut = $payment->rentOut;

        $data = array_merge(
            compact('payment', 'rentOut'),
            $this->getCompanyInfo()
        );

        $pdf = Pdf::loadView('print.rentout.voucher', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("voucher_{$payment->id}.pdf");
    }

    public function reservationForm($id)
    {
        return (new GenerateReservationFormAction())->execute($id);
    }

    public function residentialLease($id, $type = 'normal')
    {
        return (new GenerateResidentialLeaseAction())->execute($id, $type);
    }

    // ─── Private helpers ─────────────────────────────────────────────

    private function getCompanyInfo(): array
    {
        return [
            'companyName' => Configuration::where('key', 'company_name')->value('value') ?? config('app.name'),
            'companyAddress' => Configuration::where('key', 'company_address')->value('value') ?? '',
            'companyPhone' => Configuration::where('key', 'company_phone')->value('value') ?? '',
            'companyEmail' => Configuration::where('key', 'company_email')->value('value') ?? '',
            'companyLogo' => $this->getCompanyLogoPath(),
        ];
    }

    private function getCompanyLogoPath(): ?string
    {
        $logo = Configuration::where('key', 'logo')->value('value');
        if (! $logo) {
            return null;
        }

        $path = storage_path('app/public/'.$logo);

        return file_exists($path) ? $path : null;
    }

    /**
     * Build view data for the common payment receipt (sale receipts & sale return payments).
     *
     * @param  array<string, string>  $options  receiptTitle, referenceColumnLabel, referenceKey, footerMessage, pageTitle
     */
    private function getPaymentReceiptViewData(Request $request, array $options = []): array
    {
        $customerName = $request->input('customer_name', 'Customer');
        $paymentDate = $request->input('payment_date', date('Y-m-d'));
        $paymentMethodId = $request->input('payment_method_id') ?: $request->input('payment_method');
        $totalAmount = $request->input('total_amount', 0);
        $receiptData = json_decode($request->input('receipt_data', '[]'), true);

        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_phone')->value('value') ?? '';
        $companyEmail = Configuration::where('key', 'company_email')->value('value') ?? '';
        $gstNo = Configuration::where('key', 'gst_no')->value('value') ?? '';

        $paymentMethod = Account::find($paymentMethodId);
        $paymentMethodName = $paymentMethod ? $paymentMethod->name : 'Cash';

        $totalDiscount = array_sum(array_column($receiptData, 'discount'));

        $data = compact(
            'customerName',
            'paymentDate',
            'paymentMethodName',
            'totalAmount',
            'receiptData',
            'companyName',
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'gstNo',
            'totalDiscount'
        );

        return array_merge($data, $options);
    }
}
