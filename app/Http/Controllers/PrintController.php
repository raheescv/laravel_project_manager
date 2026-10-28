<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseVendor\BuildStatementDataAction;
use App\Actions\RentOut\Checklist\GeneratePdfAction;
use App\Actions\RentOut\GenerateReservationFormAction;
use App\Actions\RentOut\GenerateResidentialLeaseAction;
use App\Helpers\Facades\SaleHelper;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Journal;
use App\Models\ProjectContract;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentTerm;
use App\Models\RentOut;
use App\Models\RentOutRoi;
use App\Models\RentOutTransaction;
use App\Services\CompanyLogoResolver;
use App\Support\ProjectContract\ApprovalGate;
use App\Support\RentOutConfig;
use App\Traits\RendersEscPosReceipts;
use App\Traits\UsesBrowsershot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintController extends Controller
{
    use RendersEscPosReceipts;
    use UsesBrowsershot;

    public function saleInvoice($id)
    {
        $invoice = SaleHelper::saleInvoice($id);

        // Receipt printers take the invoice as ESC/POS; see RendersEscPosReceipts.
        if ($this->wantsEscPos() && is_string($invoice)) {
            return $this->escPosResponse($invoice);
        }

        return $invoice;
    }

    public function rentOutChecklist($id)
    {
        $pdf = (new GeneratePdfAction())->execute((int) $id);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="move_in_out_checklist.pdf"');
    }

    public function daySessionReport($id)
    {
        return SaleHelper::daySessionReport($id);
    }

    public function daySessionReportCombined($id)
    {
        return SaleHelper::daySessionReport($id, true);
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

        return $this->browsershotPdf('print.rentout.statement', $data, 'rentout_statement.pdf');
    }

    /**
     * A vendor statement for one project contract, laid out like the rent-out
     * statement: payment terms are what falls due (debit), posted payments what
     * was paid (credit). Reversed payments are left out, as they never stood.
     */
    public function projectContractStatement(int $id, ?string $fromDate = null, ?string $toDate = null)
    {
        $contract = ProjectContract::with(['vendor', 'branch', 'terms', 'payments.paymentMethod', 'payments.allocations.term'])->findOrFail($id);

        $entries = collect();
        foreach ($contract->terms as $term) {
            $entries->push([
                'date' => $term->due_date,
                'description' => $term->label,
                'payment_mode' => 'Term due',
                'cheque_no' => '',
                'debit' => (float) $term->amount,
                'credit' => 0,
                'remark' => $term->remarks ?? '',
            ]);
        }
        foreach ($contract->payments->where('status', ProjectContractPayment::STATUS_POSTED) as $payment) {
            $entries->push([
                'date' => $payment->date,
                'description' => $payment->voucher_no.' · '.$payment->allocations->map(fn ($allocation) => $allocation->term?->label)->filter()->implode(', '),
                'payment_mode' => $payment->paymentMethod?->name ?? '',
                'cheque_no' => $payment->cheque_no ?? '',
                'debit' => 0,
                'credit' => (float) $payment->amount,
                'remark' => trim(implode(' · ', array_filter([$payment->reference, $payment->remarks]))),
            ]);
        }

        if ($fromDate && $toDate) {
            $entries = $entries->filter(fn (array $entry): bool => Carbon::parse($entry['date'])->betweenIncluded($fromDate, $toDate));
        }

        $entries = $entries->sortBy(fn (array $entry): string => Carbon::parse($entry['date'])->format('Y-m-d').($entry['credit'] > 0 ? '1' : '0'))->values();

        $data = array_merge(
            compact('contract', 'entries', 'fromDate', 'toDate'),
            ['paidTotal' => $contract->paidTotal()],
            $this->getCompanyInfo()
        );

        return $this->browsershotPdf('print.project-contract.statement', $data, 'contract_statement_'.$contract->reference_no.'.pdf');
    }

    /**
     * The contract itself as a one-page voucher: what was agreed with the
     * vendor, for how much and how long, and who approved it.
     */
    public function projectContractVoucher(int $id)
    {
        $contract = ProjectContract::with(['vendor', 'branch', 'creator', 'terms', 'extensions' => fn ($query) => $query->where('status', 'approved')->orderBy('id')])->findOrFail($id);
        $approvals = ApprovalGate::round($contract);

        $data = array_merge(
            compact('contract', 'approvals'),
            ['paidTotal' => $contract->paidTotal()],
            $this->getCompanyInfo()
        );

        return $this->browsershotPdf('print.project-contract.contract-voucher', $data, 'contract_'.$contract->reference_no.'.pdf');
    }

    /**
     * One payment term as an invoice (the amount due) or as the receipt /
     * voucher for what has been paid on it so far, across all its payments.
     */
    public function projectContractTermDocument(string $kind, int $id)
    {
        $term = ProjectContractPaymentTerm::with(['contract.vendor', 'contract.branch'])->whereHas('contract')->findOrFail($id);
        $contract = $term->contract;
        $allocations = $term->allocations()
            ->whereHas('payment', fn ($query) => $query->where('status', ProjectContractPayment::STATUS_POSTED))
            ->with('payment.paymentMethod')
            ->get()
            ->sortBy(fn ($allocation) => $allocation->payment->date)
            ->values();

        abort_if($kind !== 'invoice' && $allocations->isEmpty(), 404, 'Nothing has been paid on this term yet.');

        $data = array_merge(
            compact('term', 'contract', 'allocations', 'kind'),
            $this->getCompanyInfo()
        );

        return $this->browsershotPdf('print.project-contract.term', $data, "{$kind}_{$contract->reference_no}_T{$term->id}.pdf");
    }

    /** The receipt the vendor signs for one contract payment. */
    public function projectContractPaymentReceipt(int $id)
    {
        return $this->projectContractPaymentDocument($id, 'receipt');
    }

    /** The accounts voucher for one contract payment. */
    public function projectContractPaymentVoucher(int $id)
    {
        return $this->projectContractPaymentDocument($id, 'voucher');
    }

    private function projectContractPaymentDocument(int $id, string $kind)
    {
        $payment = ProjectContractPayment::with(['paymentMethod', 'creator', 'allocations.term', 'contract.vendor', 'contract.branch'])
            ->whereHas('contract')
            ->findOrFail($id);
        $contract = $payment->contract;

        $data = array_merge(
            compact('payment', 'contract', 'kind'),
            $this->getCompanyInfo()
        );

        return $this->browsershotPdf('print.project-contract.payment', $data, "{$kind}_{$payment->voucher_no}.pdf");
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
                'utility' => $uTerm->utility->name ?? '',
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

        return $this->browsershotPdf('print.rentout.utilities-statement', $data, 'rentout_utilities_statement.pdf');
    }

    public function rentOutPaymentReceipt($id)
    {
        $payment = RentOutTransaction::with(['account', 'rentOut.customer', 'rentOut.property', 'rentOut.building'])->findOrFail($id);
        $rentOut = $payment->rentOut;

        $data = array_merge(
            compact('payment', 'rentOut'),
            $this->getCompanyInfo()
        );

        return $this->browsershotPdf('print.rentout.receipt', $data, "receipt_{$payment->id}.pdf");
    }

    public function rentOutPaymentVoucher($id)
    {
        $payment = RentOutTransaction::with(['account', 'rentOut.customer', 'rentOut.property', 'rentOut.building'])->findOrFail($id);
        $rentOut = $payment->rentOut;
        // An ROI row prints as the ROI invoice, so it needs the period and rate it was booked with.
        $roi = $payment->source === RentOutRoi::SOURCE ? RentOutRoi::find($payment->model_id) : null;

        $data = array_merge(
            compact('payment', 'rentOut', 'roi'),
            $this->getCompanyInfo()
        );

        return $this->browsershotPdf('print.rentout.voucher', $data, "voucher_{$payment->id}.pdf");
    }

    public function purchaseVendorStatement(int $id, ?string $fromDate = null, ?string $toDate = null)
    {
        $vendor = Account::vendor()->findOrFail($id);
        $fromDate ??= now()->subMonths(3)->format('Y-m-d');
        $toDate ??= now()->format('Y-m-d');

        $statementData = app(BuildStatementDataAction::class)->execute($vendor, $fromDate, $toDate);
        $summary = $statementData['summary'];
        $statementRows = collect($statementData['rows'])->map(function (array $row) use ($vendor) {
            $row['payment_voucher_url'] = ! empty($row['can_view_payment_voucher'])
                ? route('print::purchase_vendor::payment-voucher', ['vendorId' => $vendor->id, 'journalId' => $row['journal_id']])
                : null;

            return (object) $row;
        });

        $data = array_merge(
            compact(
                'vendor',
                'statementRows',
                'fromDate',
                'toDate',
            ),
            $this->getCompanyInfo(),
            [
                'openingDebit' => $summary['opening_debit'],
                'openingCredit' => $summary['opening_credit'],
                'openingBalanceLabel' => $summary['opening_balance_label'],
                'periodDebit' => $summary['period_debit'],
                'periodCredit' => $summary['period_credit'],
                'totalDebit' => $summary['total_debit'],
                'totalCredit' => $summary['total_credit'],
                'closingBalanceLabel' => $summary['closing_balance_label'],
            ],
        );

        // The view declares "@page { margin: 0 }" and manages its own padding.
        return $this->browsershotPdf('print.purchase-vendor.statement', $data, sprintf(
            'vendor-statement-%s-%s-to-%s.pdf',
            Str::slug($vendor->name ?: 'vendor'),
            $fromDate,
            $toDate,
        ), ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]);
    }

    public function purchaseVendorPaymentVoucher(int $vendorId, int $journalId)
    {
        $vendor = Account::vendor()->findOrFail($vendorId);
        $journal = Journal::query()
            ->with(['entries.account', 'createdBy'])
            ->findOrFail($journalId);

        $hasPurchasePaymentEntry = $journal->entries->contains(function ($entry) use ($vendor): bool {
            return (int) $entry->account_id === (int) $vendor->id
                && $entry->model === 'PurchasePayment'
                && (float) $entry->debit > 0;
        });

        abort_unless($hasPurchasePaymentEntry, 404);

        return $this->browsershotPdf('print.purchase-vendor.voucher', array_merge(
            compact('journal', 'vendor'),
            $this->getCompanyInfo()
        ), sprintf(
            'payment-voucher-%s-%s.pdf',
            Str::slug($vendor->name ?: 'vendor'),
            $journal->reference_number ?: $journal->id,
        ));
    }

    public function reservationForm($id)
    {
        $this->authorizeBookingPrint($id, 'bookingReservationFormPermission');

        return (new GenerateReservationFormAction())->execute($id);
    }

    public function residentialLease($id, $type = 'normal')
    {
        $this->authorizeBookingPrint($id, 'bookingResidentialLeasePermission');

        return (new GenerateResidentialLeaseAction())->execute($id, $type);
    }

    // ─── Private helpers ─────────────────────────────────────────────

    /**
     * Booking prints share one route across rental and sale bookings, so the
     * permission group ('rent out booking' vs 'rent out lease booking') is
     * resolved from the record's own agreement type.
     */
    private function authorizeBookingPrint(int|string $id, string $permissionProperty): void
    {
        $rentOut = RentOut::findOrFail($id);
        $config = RentOutConfig::make($rentOut->agreement_type->value);

        abort_unless(auth()->user()?->can($config->{$permissionProperty}), 403);
    }

    /**
     * Render a print view to PDF through Browsershot (Chrome).
     *
     * These documents print tenant-editable company names and customer names,
     * which in this market are routinely Arabic — dompdf renders Arabic
     * unshaped and back-to-front, so anything carrying free text must go
     * through Chrome. Browsershot blocks external requests, so the logo is
     * swapped to an embedded data URI before rendering.
     *
     * @param  array{top: int, right: int, bottom: int, left: int}|null  $margins  mm; null = 10mm all round
     */
    private function browsershotPdf(string $view, array $data, string $filename, ?array $margins = null)
    {
        $data['companyLogo'] = CompanyLogoResolver::dataUri();
        $html = view($view, $data)->render();

        $margins ??= ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];

        $pdf = $this->makeBrowsershot($html)
            ->format('A4')
            ->margins($margins['top'], $margins['right'], $margins['bottom'], $margins['left'])
            ->showBackground()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    private function getCompanyInfo(): array
    {
        return [
            'companyName' => Configuration::where('key', 'company_name')->value('value') ?? config('app.name'),
            'companyAddress' => Configuration::where('key', 'company_address')->value('value') ?? '',
            'companyPhone' => Configuration::where('key', 'company_phone')->value('value') ?: (Configuration::where('key', 'mobile')->value('value') ?? ''),
            'companyEmail' => Configuration::where('key', 'company_email')->value('value') ?: (Configuration::where('key', 'email')->value('value') ?? ''),
            'companyWebsite' => Configuration::where('key', 'company_website')->value('value') ?? '',
            'companyLogo' => $this->getCompanyLogoPath(),
        ];
    }

    private function getCompanyLogoPath(): ?string
    {
        // DomPDF renders local file paths reliably (it can't always fetch remote
        // URLs and drops data-URIs), so resolve to an on-disk path.
        return CompanyLogoResolver::path();
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
