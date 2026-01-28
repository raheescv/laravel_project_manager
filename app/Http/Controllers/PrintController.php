<?php

namespace App\Http\Controllers;

use App\Helpers\Facades\SaleHelper;
use App\Models\Account;
use App\Models\Configuration;
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

    /**
     * Build view data for the common payment receipt (sale receipts & sale return payments).
     *
     * @param  array<string, string>  $options  receiptTitle, referenceColumnLabel, referenceKey, footerMessage, pageTitle
     */
    private function getPaymentReceiptViewData(Request $request, array $options = []): array
    {
        $customerName = $request->input('customer_name', 'Customer');
        $paymentDate = $request->input('payment_date', date('Y-m-d'));
        $paymentMethodId = $request->input('payment_method_id');
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
