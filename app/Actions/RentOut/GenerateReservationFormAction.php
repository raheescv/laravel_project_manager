<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Traits\UsesBrowsershot;

class GenerateReservationFormAction
{
    use UsesBrowsershot;

    public function execute(int $id): \Illuminate\Http\Response
    {
        $rentOut = RentOut::with([
            'customer', 'property.building.group', 'property.type',
            'building', 'group', 'type', 'salesman',
        ])
            ->withTrashed()
            ->findOrFail($id);

        $isLease = $rentOut->agreement_type === AgreementType::Lease;

        $propertyDetails = $this->buildPropertyDetails($rentOut, $isLease);
        $buyerDetails = $this->buildBuyerDetails($rentOut);
        $agentDetails = $this->buildAgentDetails($rentOut);

        $html = view('print.booking.reservation-form', compact(
            'rentOut', 'propertyDetails', 'buyerDetails', 'agentDetails'
        ))->render();

        $pdf = $this->makeBrowsershot($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->preferCssPageSize()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reservation-form-'.time().'.pdf"');
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
                'english' => ['title' => 'Reservation Status', 'value' => ucfirst($rentOut->booking_status?->value)],
                'arabic' => ['title' => 'حالة الحجز', 'value' => ucfirst($rentOut->booking_status?->value)],
            ],
        ];
    }
}
