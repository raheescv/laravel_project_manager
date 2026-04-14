<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\Configuration;
use App\Models\RentOut;
use Spatie\Browsershot\Browsershot;

class GenerateResidentialLeaseAction
{
    public function execute(int $id, string $type = 'normal'): \Illuminate\Http\Response
    {
        $rentOut = RentOut::with([
            'customer', 'property.building.group', 'property.type',
            'building', 'group', 'type', 'paymentTerms', 'securities', 'extends',
        ])
            ->withTrashed()
            ->findOrFail($id);

        if ($rentOut->agreement_type === AgreementType::Rental) {
            $html = $this->buildRentalLeaseHtml($rentOut, $type);
        } else {
            $html = $this->buildSaleLeaseHtml($rentOut);
        }

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

    // ─── Rental Lease ────────────────────────────────────────────────

    private function buildRentalLeaseHtml(RentOut $rentOut, string $type): string
    {
        $lessorData = $this->buildLessorData($rentOut);
        $lesseeData = $this->buildLesseeData($rentOut);
        $premisesDetails = $this->buildPremisesDetails($rentOut);
        $contractDetails = $this->buildContractDetails($rentOut, $type);

        $rentOutExtend = $rentOut->extends()->latest()->first();
        $title = 'RESIDENTIAL LEASE';
        if ($rentOutExtend) {
            $title = 'EXTENDED RESIDENTIAL LEASE';
        }

        return view('print.booking.rental-residential-lease', compact(
            'rentOut', 'lessorData', 'lesseeData', 'premisesDetails', 'contractDetails', 'title', 'type'
        ))->render();
    }

    // ─── Sale Lease ──────────────────────────────────────────────────

    private function buildSaleLeaseHtml(RentOut $rentOut): string
    {
        $words = convert_number_to_words(round($rentOut->rent));
        $numberToWord = [
            'english' => $words,
            'arabic' => $words, // Arabic translation can be added later if needed
        ];

        return view('print.booking.sale-residential-lease', compact(
            'rentOut', 'numberToWord'
        ))->render();
    }

    // ─── Shared Data Builders ────────────────────────────────────────

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
}
