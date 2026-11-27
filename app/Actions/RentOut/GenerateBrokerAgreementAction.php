<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Support\RentOutPrintSettings;
use App\Traits\UsesBrowsershot;
use Illuminate\Http\Response;

/**
 * Exclusive Leasing Brokerage Agreement for a sale (lease) agreement: the unit
 * owner (the sale's customer) appoints the broker to lease the unit out.
 * Broker details, clauses and bond paper layout come from Settings → Rent Out.
 */
class GenerateBrokerAgreementAction
{
    use UsesBrowsershot;

    public function execute(int $id): Response
    {
        $rentOut = RentOut::with(['customer', 'property.type', 'building', 'group', 'type'])
            ->withTrashed()
            ->findOrFail($id);

        abort_unless($rentOut->agreement_type === AgreementType::Lease, 404);

        $pdf = $this->makeBrowsershot($this->html($rentOut))
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="broker-agreement-'.$rentOut->id.'.pdf"');
    }

    public function html(RentOut $rentOut): string
    {
        $bondPaperMode = Configuration::where('key', 'broker_bond_paper_mode')->value('value') === 'yes';
        $logoHeight = (int) (Configuration::where('key', 'broker_logo_height')->value('value') ?: 80);

        return view('print.booking.broker-agreement', [
            'rentOut' => $rentOut,
            'projectNameEnglish' => $this->projectName($rentOut, 'english'),
            'projectNameArabic' => $this->projectName($rentOut, 'arabic'),
            'brokerNameEnglish' => RentOutPrintSettings::broker('broker_name_english'),
            'brokerNameArabic' => RentOutPrintSettings::broker('broker_name_arabic') ?: RentOutPrintSettings::broker('broker_name_english'),
            'signatoryName' => trim((string) $rentOut->customer?->name),
            'sections' => $this->sections($rentOut),
            'clauses' => RentOutPrintSettings::brokerClauses(),
            'agreementColors' => RentOutPrintSettings::colors(),
            'headerLogoUrl' => $this->storedImageDataUri('lease_residential_logo'),
            'footerLogoUrl' => $this->storedImageDataUri('rent_out_agreement_logo_footer', trimBlankRows: true),
            'bondPaperMode' => $bondPaperMode,
            'logoHeight' => $logoHeight,
            'footerHeight' => (int) (Configuration::where('key', 'broker_footer_height')->value('value') ?: 50),
            // Page 2 onward opens straight on a section bar, so it needs a little more room
            // than page 1 (whose title block has its own padding) to clear the letterhead.
            'nextPagesHeaderHeight' => $logoHeight + 25,
        ])->render();
    }

    /**
     * Each row is [English title, English value, Arabic value, Arabic title];
     * a blank value prints as a line to fill in by hand.
     *
     * @return list<array{english: string, arabic: string, rows: list<array{0: string, 1: ?string, 2: ?string, 3: string}>}>
     */
    public function sections(RentOut $rentOut): array
    {
        $customer = $rentOut->customer;
        $property = $rentOut->property;
        $building = $rentOut->building;
        $type = $rentOut->type ?? $property?->type;

        $agreementDate = $rentOut->broker_agreement_date ?: $rentOut->created_at;
        // No fallback to rent: on a sale agreement that column holds the sale price.
        $askingRent = $this->amount($rentOut->asking_rent);
        $minimumRent = $this->amount($rentOut->minimum_rent);
        $availableFrom = $rentOut->available_from ?: $rentOut->start_date;
        $ownerQidCr = implode(' / ', array_filter([trim((string) $customer?->id_no), trim((string) $customer?->cr_number)]));
        $representative = RentOutPrintSettings::broker('broker_authorized_by_english');

        $projectEnglish = $this->projectName($rentOut, 'english');
        $projectArabic = $this->projectName($rentOut, 'arabic');
        $location = trim((string) $building?->location);

        $date = $agreementDate ? systemDate($agreementDate) : '';
        $available = $availableFrom ? systemDate($availableFrom) : '';
        [$incentiveEnglish, $incentiveArabic] = $this->launchIncentive($rentOut);

        return [
            [
                'english' => 'AGREEMENT',
                'arabic' => 'العقد',
                'rows' => [
                    ['Date', $date, $date, 'التاريخ'],
                    ['Agreement No.', $rentOut->agreement_no, $rentOut->agreement_no, 'رقم العقد'],
                ],
            ],
            [
                'english' => 'BROKER',
                'arabic' => 'الوسيط',
                'rows' => [
                    ['Name', RentOutPrintSettings::broker('broker_name_english'), RentOutPrintSettings::broker('broker_name_arabic') ?: RentOutPrintSettings::broker('broker_name_english'), 'الاسم'],
                    ['CR No.', RentOutPrintSettings::broker('broker_cr_no'), RentOutPrintSettings::broker('broker_cr_no'), 'سجل تجاري رقم'],
                    ['Brokerage Licence No.', RentOutPrintSettings::broker('broker_license_no'), RentOutPrintSettings::broker('broker_license_no'), 'ترخيص الوساطة رقم'],
                    ['Represented by', $representative, RentOutPrintSettings::broker('broker_authorized_by_arabic') ?: $representative, 'ويمثلها'],
                ],
            ],
            [
                'english' => 'OWNER',
                'arabic' => 'المالك',
                'rows' => [
                    ['Name', $customer?->name, $customer?->name, 'الاسم'],
                    ['QID/CR', $ownerQidCr, $ownerQidCr, 'البطاقة الشخصية/السجل التجاري'],
                    ['Mobile', $customer?->mobile, $customer?->mobile, 'الهاتف'],
                    ['Email', $customer?->email, $customer?->email, 'البريد الإلكتروني'],
                ],
            ],
            [
                'english' => 'PROPERTY',
                'arabic' => 'العقار',
                'rows' => [
                    ['Property', $location !== '' ? $projectEnglish.', '.$location : $projectEnglish, $location !== '' ? $projectArabic.'، '.$location : $projectArabic, 'العقار'],
                    ['Tower', $building?->name, $building?->arabic_name ?: $building?->name, 'البرج'],
                    ['Unit', $property?->number, $property?->number, 'الوحدة'],
                    ['Floor', $property?->floor, $property?->floor, 'الطابق'],
                    ['Type', $type?->name, $type?->arabic_name ?: $type?->name, 'النوع'],
                    ['Parking', $property?->parking, $property?->parking, 'الموقف'],
                ],
            ],
            [
                'english' => 'RENT',
                'arabic' => 'الإيجار',
                'rows' => [
                    ['Approved asking rent', $askingRent ? 'QAR '.$askingRent.' / month' : '', $askingRent ? $askingRent.' ر.ق / شهرياً' : '', 'الإيجار المطلوب المعتمد'],
                    ['Minimum rent', $minimumRent ? 'QAR '.$minimumRent : '', $minimumRent ? $minimumRent.' ر.ق' : '', 'الحد الأدنى'],
                    ['Available from', $available, $available, 'تاريخ التوفر'],
                    ['Launch incentive (if approved)', $incentiveEnglish, $incentiveArabic, 'العرض الترويجي المعتمد (إن وجد)'],
                ],
            ],
        ];
    }

    /** The building's group names the project; the configured tenancy project name fills in when there is none. */
    private function projectName(RentOut $rentOut, string $language): string
    {
        $english = $rentOut->group?->name ?: RentOutPrintSettings::text('tenancy_project_name_english');
        if ($language === 'english') {
            return (string) $english;
        }

        return (string) ($rentOut->group?->arabic_name ?: RentOutPrintSettings::text('tenancy_project_name_arabic') ?: $english);
    }

    /**
     * The incentive typed on the agreement, else its free months ("2 months rent free").
     *
     * @return array{0: string, 1: string}
     */
    private function launchIncentive(RentOut $rentOut): array
    {
        $freeMonths = (float) $rentOut->free_month;
        $months = $this->amount($freeMonths);
        $english = trim((string) $rentOut->launch_incentive_en);
        $arabic = trim((string) $rentOut->launch_incentive_ar);

        if ($english === '' && $freeMonths > 0) {
            $english = $months.' '.($freeMonths == 1 ? 'month' : 'months').' rent free';
        }
        if ($arabic === '') {
            $arabic = filled($rentOut->launch_incentive_en)
                ? $english
                : ($freeMonths > 0 ? $months.' '.($freeMonths == 1 ? 'شهر' : 'أشهر').' مجاناً' : '');
        }

        return [$english, $arabic];
    }

    /** 12500.50 → "12,500.5"; blank or zero → "". */
    private function amount(mixed $value): string
    {
        if ($value === null || $value === '' || (float) $value == 0) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    }

    /**
     * A logo uploaded in Settings → Rent Out as a data URI (Browsershot blocks
     * every external request). The footer artwork is cropped to its visible
     * rows so the fixed footer takes only the height it needs.
     */
    private function storedImageDataUri(string $configKey, bool $trimBlankRows = false): ?string
    {
        $stored = Configuration::where('key', $configKey)->value('value');
        $path = $stored ? storage_path('app/public/'.$stored) : null;
        if (! $path || ! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);
        if ($trimBlankRows && ($trimmed = $this->trimBlankRows($contents))) {
            return 'data:image/png;base64,'.base64_encode($trimmed);
        }

        return 'data:image/'.pathinfo($path, PATHINFO_EXTENSION).';base64,'.base64_encode($contents);
    }

    /** PNG bytes of the image without its blank top and bottom rows, or null when nothing to crop. */
    private function trimBlankRows(string $contents): ?string
    {
        $image = @imagecreatefromstring($contents);
        if (! $image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $isBlankRow = function (int $y) use ($image, $width): bool {
            for ($x = 0; $x < $width; $x += 2) {
                $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                if ($rgba['alpha'] < 120 && min($rgba['red'], $rgba['green'], $rgba['blue']) < 245) {
                    return false;
                }
            }

            return true;
        };

        $top = 0;
        while ($top < $height - 1 && $isBlankRow($top)) {
            $top++;
        }
        $bottom = $height - 1;
        while ($bottom > $top && $isBlankRow($bottom)) {
            $bottom--;
        }
        if ($top === 0 && $bottom === $height - 1) {
            return null;
        }

        $cropped = imagecrop($image, ['x' => 0, 'y' => $top, 'width' => $width, 'height' => $bottom - $top + 1]);
        if (! $cropped) {
            return null;
        }

        imagesavealpha($cropped, true);
        ob_start();
        imagepng($cropped);

        return (string) ob_get_clean();
    }
}
