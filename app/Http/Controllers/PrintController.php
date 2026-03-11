<?php

namespace App\Http\Controllers;

use App\Helpers\Facades\SaleHelper;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\RentOut;
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

    public function rentoutStatement($id)
    {
        $rentOut = RentOut::with([
            'customer', 'property', 'building', 'group', 'type',
            'paymentTerms', 'journals',
        ])->findOrFail($id);

        $payments = collect();

        // Add payment term debits
        foreach ($rentOut->paymentTerms as $term) {
            $payments->push([
                'date' => $term->due_date?->format('d-m-Y') ?? '',
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
                    'date' => $journal->date?->format('d-m-Y') ?? '',
                    'payment_mode' => $journal->payment_mode ?? '',
                    'cheque_no' => $journal->cheque_no ?? '',
                    'debit' => 0,
                    'credit' => $journal->credit ?? 0,
                    'remark' => $journal->remark ?? '',
                ]);
            }
        }

        return view('print.rentout.statement', compact('rentOut', 'payments'));
    }

    public function rentoutUtilitiesStatement($id)
    {
        $rentOut = RentOut::with([
            'customer', 'property', 'building', 'group', 'type',
            'utilityTerms.utility', 'journals',
        ])->findOrFail($id);

        $payments = collect();

        // Add utility term debits
        foreach ($rentOut->utilityTerms as $uTerm) {
            $payments->push([
                'date' => $uTerm->date?->format('d-m-Y') ?? '',
                'utility' => $uTerm->utility?->name ?? '',
                'payment_mode' => 'Utility Due',
                'debit' => $uTerm->amount ?? 0,
                'credit' => 0,
                'remark' => $uTerm->remarks ?? '',
            ]);
        }

        // Add utility payment credits from journals
        foreach ($rentOut->journals as $journal) {
            if (($journal->credit ?? 0) > 0 && str_contains(strtolower($journal->category ?? ''), 'utility')) {
                $payments->push([
                    'date' => $journal->date?->format('d-m-Y') ?? '',
                    'utility' => $journal->category ?? '',
                    'payment_mode' => $journal->payment_mode ?? '',
                    'debit' => 0,
                    'credit' => $journal->credit ?? 0,
                    'remark' => $journal->remark ?? '',
                ]);
            }
        }

        return view('print.rentout.utilities-statement', compact('rentOut', 'payments'));
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
