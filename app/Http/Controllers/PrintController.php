<?php

namespace App\Http\Controllers;

use App\Enums\RentOut\AgreementType;
use App\Helpers\Facades\SaleHelper;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\RentOut;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

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

    public function reservationForm($id)
    {
        $rentOut = RentOut::with(['customer', 'property.building.group', 'property.type', 'building', 'group', 'type', 'salesman'])
            ->withTrashed()
            ->findOrFail($id);

        $isLease = $rentOut->agreement_type === AgreementType::Lease;

        $propertyDetails = $this->buildPropertyDetails($rentOut, $isLease);
        $buyerDetails = $this->buildBuyerDetails($rentOut);
        $agentDetails = $this->buildAgentDetails($rentOut);

        $html = view('print.booking.reservation-form', compact(
            'rentOut', 'propertyDetails', 'buyerDetails', 'agentDetails'
        ))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->preferCssPageSize()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reservation-form-'.time().'.pdf"');
    }

    public function residentialLease($id, $type = 'normal')
    {
        $rentOut = RentOut::with(['customer', 'property.building.group', 'property.type', 'building', 'group', 'type', 'paymentTerms', 'securities', 'extends'])
            ->withTrashed()
            ->findOrFail($id);

        if ($rentOut->agreement_type !== AgreementType::Rental) {
            abort(404, 'Residential lease is only available for rental agreements.');
        }

        $lessorData = $this->buildLessorData($rentOut);
        $lesseeData = $this->buildLesseeData($rentOut);
        $premisesDetails = $this->buildPremisesDetails($rentOut);
        $contractDetails = $this->buildContractDetails($rentOut, $type);

        $rentOutExtend = $rentOut->extends()->latest()->first();
        $rent = round($rentOut->rent);
        $title = 'RESIDENTIAL LEASE';
        if ($rentOutExtend) {
            $title = 'EXTENDED RESIDENTIAL LEASE';
            $rent = $rentOutExtend->rent_amount;
        }

        $html = view('print.booking.rental-residential-lease', compact(
            'rentOut', 'lessorData', 'lesseeData', 'premisesDetails', 'contractDetails', 'title', 'type'
        ))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->preferCssPageSize()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="residential-lease-'.time().'.pdf"');
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
        $logo = Configuration::where('key', 'company_logo')->value('value');

        if (! $logo) {
            return null;
        }

        $path = storage_path('app/public/'.$logo);

        return file_exists($path) ? $path : null;
    }

    private function buildPropertyDetails(RentOut $rentOut, bool $isLease): array
    {
        $details = [];

        $details[] = [
            'english' => ['title' => 'Tower', 'value' => $rentOut->building?->name],
            'arabic' => ['title' => 'برج', 'value' => $rentOut->building?->arabic_name],
        ];
        $details[] = [
            'english' => ['title' => 'Type', 'value' => $rentOut->type?->name],
            'arabic' => ['title' => 'نوع الوحدة', 'value' => $rentOut->type?->arabic_name],
        ];
        $details[] = [
            'english' => ['title' => 'Unit Floor', 'value' => $rentOut->property?->floor],
            'arabic' => ['title' => 'الطابق'],
        ];
        $details[] = [
            'english' => ['title' => 'Unit Number', 'value' => $rentOut->property?->number],
            'arabic' => ['title' => 'ر قم الوحدة'],
        ];

        if ($isLease) {
            $details[] = [
                'english' => ['title' => 'Total Price', 'value' => 'QAR '.currency($rentOut->rent)],
                'arabic' => ['title' => 'السعر الإجمالي', 'value' => currency($rentOut->rent).' ريال قطري'],
            ];
            $details[] = [
                'english' => ['title' => 'Unit Gross Size', 'value' => $rentOut->property?->size],
                'arabic' => ['title' => 'الحجم الإجمالي للوحدة', 'value' => $rentOut->property?->size],
            ];
        } else {
            $details[] = [
                'english' => ['title' => 'Monthly Rent', 'value' => 'QAR '.currency($rentOut->rent)],
                'arabic' => ['title' => 'الإيجار الشهري', 'value' => currency($rentOut->rent).' ريال قطري'],
            ];
        }

        $stays = $rentOut->totalStay();
        $stayType = 'Months';

        if ($isLease && $stays > 12) {
            $stays = round($stays / 12, 2);
            $stayType = 'Years';
        }

        if ($isLease) {
            $details[] = [
                'english' => ['title' => 'Installment', 'value' => $stays.' '.$stayType],
                'arabic' => ['title' => 'المدة', 'value' => $stays.' '.$stayType],
            ];
        } else {
            $details[] = [
                'english' => ['title' => 'Period', 'value' => $stays.' '.$stayType],
                'arabic' => ['title' => 'المدة'],
            ];
        }

        $details[] = [
            'english' => ['title' => 'Parking Slot', 'value' => $rentOut->property?->parking ?? '1 Parking Slot'],
            'arabic' => ['title' => 'موقف السيار ة'],
        ];

        if (! $isLease) {
            $details[] = [
                'english' => ['title' => 'Contract Start Date', 'value' => systemDate($rentOut->start_date)],
                'arabic' => ['title' => 'تاريخ بدء العقد', 'value' => systemDate($rentOut->start_date)],
            ];
            $details[] = [
                'english' => ['title' => 'Contract End Date', 'value' => systemDate($rentOut->end_date)],
                'arabic' => ['title' => 'تاريخ انتهاء العقد', 'value' => systemDate($rentOut->end_date)],
            ];
        }

        return $details;
    }

    private function buildBuyerDetails(RentOut $rentOut): array
    {
        $customer = $rentOut->customer;

        return [
            [
                'english' => ['title' => 'Name', 'value' => $customer?->name],
                'arabic' => ['title' => 'اسم', 'value' => $customer?->name],
            ],
            [
                'english' => ['title' => 'QID No', 'value' => $customer?->id_no],
                'arabic' => ['title' => 'رقم الهوية القطرية', 'value' => $customer?->id_no],
            ],
            [
                'english' => ['title' => 'Mobile No', 'value' => $customer?->mobile],
                'arabic' => ['title' => 'رقم الجوال', 'value' => $customer?->mobile],
            ],
            [
                'english' => ['title' => 'E-Mail', 'value' => $customer?->email, 'keep_original' => true],
                'arabic' => ['title' => 'بريد إلكتروني'],
            ],
        ];
    }

    private function buildAgentDetails(RentOut $rentOut): array
    {
        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');

        return [
            [
                'english' => ['title' => 'AGENCY NAME', 'value' => $companyName],
                'arabic' => ['title' => 'اسم الوكالة', 'value' => $companyName],
            ],
            [
                'english' => ['title' => 'Sales Person Name', 'value' => $rentOut->salesman?->name],
                'arabic' => ['title' => 'اسم مندوب المبيعات', 'value' => $rentOut->salesman?->name],
            ],
            [
                'english' => ['title' => 'Reservation Status', 'value' => ucfirst($rentOut->booking_status ?? '')],
                'arabic' => ['title' => 'حالة الحجز', 'value' => ucfirst($rentOut->booking_status ?? '')],
            ],
        ];
    }

    private function buildLessorData(RentOut $rentOut): array
    {
        $cfg = fn ($key, $default = '') => Configuration::where('key', $key)->value('value') ?? $default;

        return [
            [
                'english' => ['title' => 'Reference No', 'value' => $rentOut->agreement_no],
                'arabic' => ['title' => 'رقم المرجع', 'value' => $rentOut->agreement_no],
            ],
            [
                'english' => ['title' => 'Name', 'value' => $cfg('lessor_name_en')],
                'arabic' => ['title' => 'اسم', 'value' => $cfg('lessor_name_ar')],
            ],
            [
                'english' => ['title' => 'P.O Box', 'value' => $cfg('lessor_po_box')],
                'arabic' => ['title' => 'صندوق البريد', 'value' => $cfg('lessor_po_box')],
            ],
            [
                'english' => ['title' => 'CR No', 'value' => $cfg('lessor_cr_no')],
                'arabic' => ['title' => 'رقم السجل التجاري', 'value' => $cfg('lessor_cr_no')],
            ],
            [
                'english' => ['title' => 'Authorized By', 'value' => $cfg('lessor_authorized_by')],
                'arabic' => ['title' => 'مفوض من قبل', 'value' => $cfg('lessor_authorized_by')],
            ],
            [
                'english' => ['title' => 'QID No', 'value' => $cfg('lessor_qid_no')],
                'arabic' => ['title' => 'رقم البطاقة الشخصية', 'value' => $cfg('lessor_qid_no')],
            ],
            [
                'english' => ['title' => 'Nationality', 'value' => $cfg('lessor_nationality')],
                'arabic' => ['title' => 'الجنسية', 'value' => $cfg('lessor_nationality')],
            ],
            [
                'english' => ['title' => 'Email', 'value' => $cfg('lessor_email')],
                'arabic' => ['title' => 'الالكتروني', 'value' => $cfg('lessor_email')],
            ],
            [
                'english' => ['title' => 'Tel/Fax/Call Centre', 'value' => $cfg('lessor_tel_fax')],
                'arabic' => ['title' => 'هاتف / فاكس / مركز الاتصال', 'value' => $cfg('lessor_tel_fax')],
            ],
        ];
    }

    private function buildLesseeData(RentOut $rentOut): array
    {
        $customer = $rentOut->customer;

        return [
            [
                'english' => ['title' => 'Name', 'value' => $customer?->name],
                'arabic' => ['title' => 'اسم', 'value' => $customer?->name],
            ],
            [
                'english' => ['title' => 'Qid No', 'value' => $customer?->id_no],
                'arabic' => ['title' => 'رقم بطاقة المستأجر', 'value' => $customer?->id_no],
            ],
            [
                'english' => ['title' => 'Mobile No', 'value' => $customer?->mobile],
                'arabic' => ['title' => 'رقم هاتف المستأجر', 'value' => $customer?->mobile],
            ],
            [
                'english' => ['title' => 'E-Mail', 'value' => $customer?->email, 'keep_original' => true],
                'arabic' => ['title' => 'بريد إلكتروني'],
            ],
        ];
    }

    private function buildPremisesDetails(RentOut $rentOut): array
    {
        return [
            [
                'english' => ['title' => 'Tower', 'value' => $rentOut->building?->name],
                'arabic' => ['title' => 'برج', 'value' => $rentOut->building?->arabic_name],
            ],
            [
                'english' => ['title' => 'Apartment type', 'value' => $rentOut->type?->name],
                'arabic' => ['title' => 'نوع الشقة', 'value' => $rentOut->type?->arabic_name],
            ],
            [
                'english' => ['title' => 'Unit No', 'value' => $rentOut->property?->number],
                'arabic' => ['title' => 'رقم الوحدة', 'value' => $rentOut->property?->number],
            ],
            [
                'english' => ['title' => 'Electricity No', 'value' => $rentOut->property?->kahramaa],
                'arabic' => ['title' => 'رقم الكهرباء', 'value' => $rentOut->property?->kahramaa],
            ],
            [
                'english' => ['title' => 'Parking Number', 'value' => $rentOut->property?->parking],
                'arabic' => ['title' => 'رقم موقف السيارة', 'value' => $rentOut->property?->parking],
            ],
        ];
    }

    private function buildContractDetails(RentOut $rentOut, string $type): array
    {
        $rentOutExtend = $rentOut->extends()->latest()->first();
        $rent = round($rentOut->rent);
        if ($rentOutExtend) {
            $rent = $rentOutExtend->rent_amount;
        }

        $details = [];

        $details[] = [
            'english' => ['title' => 'Rent', 'value' => $rent.' Qatari Riyal'],
            'arabic' => ['title' => 'إيجار', 'value' => $rent],
        ];

        // Payment Terms
        if ($type === 'normal') {
            $details[] = [
                'english' => ['title' => 'Payment Terms', 'value' => $rentOut->payment_terms_en],
                'arabic' => ['title' => 'شروط الدفع', 'value' => $rentOut->payment_terms_ar],
            ];
        } else {
            $details[] = [
                'english' => ['title' => 'Payment Terms', 'value' => $rentOut->payment_terms_extended_en],
                'arabic' => ['title' => 'شروط الدفع', 'value' => $rentOut->payment_terms_extended_ar],
            ];
        }

        // Security Deposit
        $securityDepositAmount = $rentOut->securities->sum('amount');
        $securityDepositModes = $rentOut->securities->map(fn ($s) => $s->payment_mode?->value ?? 'Cash')->unique()->implode(', ');

        $details[] = [
            'english' => ['title' => 'Security Deposit', 'value' => $securityDepositModes, 'value1' => $securityDepositAmount],
            'arabic' => ['title' => 'إيداع الضمان', 'value' => $securityDepositModes],
        ];

        // Duration
        $startDate = $rentOut->start_date;
        if ($type === 'extended' && $rentOutExtend) {
            $startDate = $rentOutExtend->start_date;
        }
        $endDate = $rentOut->end_date;
        $details[] = [
            'english' => ['title' => 'Duration', 'value' => systemDate($startDate).' / '.systemDate($endDate)],
            'arabic' => ['title' => 'مدة', 'value' => systemDate($endDate).' / '.systemDate($startDate)],
        ];

        // Included services
        $thirdPoint = [];
        if ($rentOut->include_electricity_water === 'Included') {
            $thirdPoint[] = 'Water';
            $thirdPoint[] = 'Electricity';
        }
        if ($rentOut->include_ac === 'Included') {
            $thirdPoint[] = 'Ac';
        }
        if ($rentOut->include_wifi === 'Included') {
            $thirdPoint[] = 'Wi Fi';
        }

        if ($thirdPoint) {
            $details[] = [
                'english' => ['title' => 'Term', 'value' => 'Bills Included'],
                'arabic' => ['title' => 'شرط', 'value' => 'شامل الفواتير'],
            ];
        } else {
            $details[] = [
                'english' => ['title' => 'Term', 'value' => 'Bills Excluded'],
                'arabic' => ['title' => 'شرط', 'value' => 'غير شامل الفواتير'],
            ];
        }

        $details[] = [
            'english' => ['title' => 'Purpose', 'value' => 'Residential Accommodation'],
            'arabic' => ['title' => 'الغرض', 'value' => 'سكن سكني'],
        ];

        $thirdPointText = $thirdPoint
            ? implode(' and ', array_filter([implode(', ', array_slice($thirdPoint, 0, -1)), end($thirdPoint)]))
            : 'No services are included';

        $details[] = [
            'english' => ['title' => 'Included Services', 'value' => $thirdPointText],
            'arabic' => ['title' => 'الخدمات المضمنة', 'value' => $thirdPointText],
        ];

        $details[] = [
            'english' => ['title' => 'Payment Terms', 'value' => 'In Advance'],
            'arabic' => ['title' => 'طرق الدفع', 'value' => 'مقدماً'],
        ];

        $details[] = [
            'english' => ['title' => 'Cancellation Fee', 'value' => $rentOut->cancellation_policy_en],
            'arabic' => ['title' => 'رسوم الإلغاء', 'value' => $rentOut->cancellation_policy_ar],
        ];

        return $details;
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
