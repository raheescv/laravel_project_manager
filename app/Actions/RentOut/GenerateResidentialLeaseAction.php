<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Support\RentOutPrintSettings;
use App\Traits\UsesBrowsershot;

class GenerateResidentialLeaseAction
{
    use UsesBrowsershot;

    public function execute(int $id, string $type = 'normal'): \Illuminate\Http\Response
    {
        $rentOut = RentOut::with([
            'customer', 'property.building.group', 'property.type',
            'building', 'group', 'type', 'paymentTerms', 'securities', 'extends',
        ])
            ->withTrashed()
            ->findOrFail($id);

        $isRental = $rentOut->agreement_type === AgreementType::Rental;
        $html = $isRental ? $this->buildRentalLeaseHtml($rentOut, $type) : $this->buildSaleLeaseHtml($rentOut);

        $browsershot = $this->makeBrowsershot($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground();

        if (! $isRental) {
            $browsershot->showBrowserHeaderAndFooter()
                ->headerHtml('<div></div>')
                ->footerHtml($this->saleFooterHtml());
        }

        $pdf = $browsershot->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="residential-lease-'.time().'.pdf"');
    }

    // ─── Rental Lease ────────────────────────────────────────────────

    private function buildRentalLeaseHtml(RentOut $rentOut, string $type): string
    {
        $lessorData = $this->buildLessorData($rentOut);
        $lesseeData = $this->buildLesseeData($rentOut);
        $premisesDetails = $this->buildPremisesDetails($rentOut);
        $contractDetails = $this->buildContractDetails($rentOut, $type);

        $titlePrefix = $rentOut->extends()->exists() ? 'extended_tenancy_agreement_title' : 'tenancy_agreement_title';
        $title = RentOutPrintSettings::text("{$titlePrefix}_english");
        $titleArabic = RentOutPrintSettings::text("{$titlePrefix}_arabic");

        // The building's group names the project; the configured name only fills in when there is none.
        $projectNameEnglish = $rentOut->group?->name ?: RentOutPrintSettings::text('tenancy_project_name_english');
        $projectNameArabic = $rentOut->group?->arabic_name ?: RentOutPrintSettings::text('tenancy_project_name_arabic');

        return view('print.booking.rental-residential-lease', compact(
            'rentOut', 'lessorData', 'lesseeData', 'premisesDetails', 'contractDetails', 'title', 'titleArabic',
            'projectNameEnglish', 'projectNameArabic', 'type'
        ))->render();
    }

    // ─── Sale Lease ──────────────────────────────────────────────────

    private function buildSaleLeaseHtml(RentOut $rentOut): string
    {
        $price = (int) round((float) $rentOut->rent);
        $numberToWord = [
            'english' => convert_number_to_words($price),
            'arabic' => convert_number_to_arabic_words($price),
        ];

        return view('print.booking.sale-residential-lease', compact(
            'rentOut', 'numberToWord'
        ))->render();
    }

    /**
     * "Page X of Y", drawn by Chrome at the bottom edge of the page margin the
     * template reserves. On bond paper it is lifted clear of the pre-printed
     * footer by that footer's configured height.
     */
    public function saleFooterHtml(): string
    {
        $bondPaperMode = Configuration::where('key', 'reservation_bond_paper_mode')->value('value') === 'yes';
        $footerHeight = (int) (Configuration::where('key', 'reservation_footer_height')->value('value') ?: 30);
        $liftPx = $bondPaperMode ? $footerHeight : 14;

        return '<div style="width: 100%; padding-bottom: '.$liftPx.'px; font-family: \'Times New Roman\', serif; font-size: 9px; color: #222; text-align: center;">'
            .'Page <span class="pageNumber"></span> of <span class="totalPages"></span>'
            .' &nbsp;·&nbsp; <span dir="rtl">صفحة <span class="pageNumber"></span> من <span class="totalPages"></span></span>'
            .'</div>';
    }

    // ─── Shared Data Builders ────────────────────────────────────────

    private function buildLessorData(RentOut $rentOut): array
    {
        $lessor = fn (string $key): string => RentOutPrintSettings::lessor($key);

        return [
            [
                'english' => ['title' => 'Reference No', 'value' => $rentOut->agreement_no],
                'arabic' => ['title' => 'رقم المرجع', 'value' => $rentOut->agreement_no],
            ],
            [
                'english' => ['title' => 'Name', 'value' => $lessor('lessor_name_en')],
                'arabic' => ['title' => 'اسم', 'value' => $lessor('lessor_name_ar')],
            ],
            [
                'english' => ['title' => 'P.O Box', 'value' => $lessor('lessor_po_box_english')],
                'arabic' => ['title' => 'صندوق البريد', 'value' => $lessor('lessor_po_box_arabic')],
            ],
            [
                'english' => ['title' => 'CR No', 'value' => $lessor('lessor_cr_no_english')],
                'arabic' => ['title' => 'رقم السجل التجاري', 'value' => $lessor('lessor_cr_no_arabic')],
            ],
            [
                'english' => ['title' => 'Authorized By', 'value' => $lessor('lessor_authorized_by_english')],
                'arabic' => ['title' => 'مفوض من قبل', 'value' => $lessor('lessor_authorized_by_arabic')],
            ],
            [
                'english' => ['title' => 'QID No', 'value' => $lessor('lessor_qid_no')],
                'arabic' => ['title' => 'رقم البطاقة الشخصية', 'value' => $lessor('lessor_qid_no')],
            ],
            [
                'english' => ['title' => 'Nationality', 'value' => $lessor('lessor_nationality')],
                'arabic' => ['title' => 'الجنسية', 'value' => $lessor('lessor_nationality')],
            ],
            [
                'english' => ['title' => 'Email', 'value' => $lessor('lessor_email')],
                'arabic' => ['title' => 'الالكتروني', 'value' => $lessor('lessor_email')],
            ],
            [
                'english' => ['title' => 'Tel/Fax/Call Centre', 'value' => $lessor('lessor_tel_fax')],
                'arabic' => ['title' => 'هاتف / فاكس / مركز الاتصال', 'value' => $lessor('lessor_tel_fax')],
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
        $rent = round((float) $rentOut->rent);
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
        $securityDepositModes = $rentOut->securities->map(fn ($s) => $s->payment_mode->value ?? 'Cash')->unique()->implode(', ');

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
}
