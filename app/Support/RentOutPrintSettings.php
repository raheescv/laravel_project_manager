<?php

namespace App\Support;

use App\Models\Configuration;

/**
 * Wording, lessor details and colours printed on the reservation form and the
 * tenancy agreement PDFs, edited in Settings → Rent Out Settings.
 *
 * Every value is its own configuration key (the same key names the accounts
 * app used, so the migration copies them across verbatim). A blank value falls
 * back to the default below, so an untouched tenant keeps printing what it
 * printed before these settings existed.
 */
class RentOutPrintSettings
{
    public const PRIMARY_COLOR_KEY = 'agreement_primary_color';

    public const SECONDARY_COLOR_KEY = 'agreement_secondary_color';

    public const DEFAULT_PRIMARY_COLOR = '#1b7bbc';

    public const DEFAULT_SECONDARY_COLOR = '#d9f0fb';

    /**
     * Agreement / reservation wording: key => default printed when the key is blank.
     * Arabic defaults left empty mean "print nothing", or, for project names,
     * fall back to the building's group.
     *
     * @return array<string, string>
     */
    public static function textDefaults(): array
    {
        return [
            'company_name_english' => '',
            'company_name_arabic' => '',

            'tenancy_agreement_title_english' => 'RESIDENTIAL LEASE',
            'tenancy_agreement_title_arabic' => '',
            'extended_tenancy_agreement_title_english' => 'EXTENDED RESIDENTIAL LEASE',
            'extended_tenancy_agreement_title_arabic' => '',
            'tenancy_project_name_english' => '',
            'tenancy_project_name_arabic' => '',

            'tenancy_contract_made_on_english' => 'This Contract is made on {date} by and between the First Party - Lessor and the Second Party - Lessee (details as above).',
            'tenancy_contract_made_on_arabic' => '(التفاصيل على النحو الوارد أعلاه). بين الطرف الأول - المؤجر والطرف الثاني - المستأجر {date} تم إبرام هذا العقد على',
            'tenancy_contract_terms_english' => 'Both the parties agree to conclude this contract according to the terms and conditions attached in page numbers 2 to 8.',
            'tenancy_contract_terms_arabic' => 'يتفق الطرفان على إبرام هذا العقد وفقاً للشروط والأحكام المرفقة في الصفحات من 2 إلى 8.',

            'reservation_form_title_english' => 'Reservation Form For An Apartment',
            'reservation_form_title_arabic' => 'نموذج تأكيد وحجز شقة',
            'reservation_project_name_english' => '',
            'reservation_project_name_arabic' => '',
        ];
    }

    /**
     * Lessor (landlord) block of the tenancy agreement. Keys without a language
     * suffix print the same value on both sides.
     *
     * @return array<string, string>
     */
    public static function lessorKeys(): array
    {
        return [
            'lessor_name_en' => 'Name (English)',
            'lessor_name_ar' => 'Name (Arabic)',
            'lessor_po_box_english' => 'P.O Box (English)',
            'lessor_po_box_arabic' => 'P.O Box (Arabic)',
            'lessor_cr_no_english' => 'CR No (English)',
            'lessor_cr_no_arabic' => 'CR No (Arabic)',
            'lessor_authorized_by_english' => 'Authorized By (English)',
            'lessor_authorized_by_arabic' => 'Authorized By (Arabic)',
            'lessor_qid_no' => 'QID No',
            'lessor_nationality' => 'Nationality',
            'lessor_email' => 'Email',
            'lessor_tel_fax' => 'Tel/Fax/Call Centre',
        ];
    }

    /**
     * Single-language keys this app read before the English/Arabic split; still
     * honoured so a tenant that set them keeps its lessor details.
     */
    private const LEGACY_LESSOR_KEYS = [
        'lessor_po_box_english' => 'lessor_po_box',
        'lessor_po_box_arabic' => 'lessor_po_box',
        'lessor_cr_no_english' => 'lessor_cr_no',
        'lessor_cr_no_arabic' => 'lessor_cr_no',
        'lessor_authorized_by_english' => 'lessor_authorized_by',
        'lessor_authorized_by_arabic' => 'lessor_authorized_by',
    ];

    /** @return array<int, array{name: string, primary: string, secondary: string}> */
    public static function colorPresets(): array
    {
        return [
            ['name' => 'Blue (Default)', 'primary' => '#1b7bbc', 'secondary' => '#d9f0fb'],
            ['name' => 'Navy', 'primary' => '#1e3a8a', 'secondary' => '#dbe4f5'],
            ['name' => 'Teal', 'primary' => '#0f766e', 'secondary' => '#d5efec'],
            ['name' => 'Green', 'primary' => '#1a7f4b', 'secondary' => '#d9f0e2'],
            ['name' => 'Maroon', 'primary' => '#8a1c2b', 'secondary' => '#f3dde0'],
            ['name' => 'Purple', 'primary' => '#5b3a9a', 'secondary' => '#e6dff3'],
            ['name' => 'Gold', 'primary' => '#b8860b', 'secondary' => '#f5ecd2'],
            ['name' => 'Charcoal', 'primary' => '#374151', 'secondary' => '#e5e7eb'],
        ];
    }

    /** Every key this class owns, for loading and saving the settings form. */
    public static function keys(): array
    {
        return [
            ...array_keys(self::textDefaults()),
            ...array_keys(self::lessorKeys()),
            self::PRIMARY_COLOR_KEY,
            self::SECONDARY_COLOR_KEY,
        ];
    }

    /** Raw stored values (blank when unset), keyed by configuration key. */
    public static function stored(): array
    {
        $values = Configuration::whereIn('key', self::keys())->pluck('value', 'key');

        return collect(self::keys())->mapWithKeys(fn (string $key) => [$key => (string) ($values[$key] ?? '')])->all();
    }

    /** The configured wording, or its default when blank. */
    public static function text(string $key): string
    {
        $value = trim((string) Configuration::where('key', $key)->value('value'));

        return $value !== '' ? $value : (self::textDefaults()[$key] ?? '');
    }

    /** The configured company name for agreements, falling back to the company profile name. */
    public static function companyName(string $language = 'english'): string
    {
        $name = self::text("company_name_{$language}");
        if ($name !== '' || $language !== 'english') {
            return $name;
        }

        return (string) (Configuration::where('key', 'company_name')->value('value') ?: config('app.name'));
    }

    public static function lessor(string $key): string
    {
        $value = trim((string) Configuration::where('key', $key)->value('value'));
        if ($value === '' && isset(self::LEGACY_LESSOR_KEYS[$key])) {
            $value = trim((string) Configuration::where('key', self::LEGACY_LESSOR_KEYS[$key])->value('value'));
        }

        return $value;
    }

    /** Replace the {date} token in a contract clause. */
    public static function clause(string $key, string $date): string
    {
        return str_replace('{date}', $date, self::text($key));
    }

    /** A contract clause escaped for HTML, with the {date} token printed in bold. */
    public static function clauseHtml(string $key, string $date): string
    {
        return str_replace('{date}', '<b>'.e($date).'</b>', e(self::text($key)));
    }

    /**
     * Agreement colours with a readable ink for text drawn on the primary colour.
     *
     * @return array{primary: string, secondary: string, primaryInk: string}
     */
    public static function colors(): array
    {
        $primary = (string) Configuration::where('key', self::PRIMARY_COLOR_KEY)->value('value');
        $secondary = (string) Configuration::where('key', self::SECONDARY_COLOR_KEY)->value('value');
        $primary = self::isHexColor($primary) ? $primary : self::DEFAULT_PRIMARY_COLOR;
        $secondary = self::isHexColor($secondary) ? $secondary : self::DEFAULT_SECONDARY_COLOR;

        return ['primary' => $primary, 'secondary' => $secondary, 'primaryInk' => self::inkFor($primary)];
    }

    public static function isHexColor(?string $value): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $value);
    }

    /** White on dark colours, dark ink on light ones. */
    public static function inkFor(string $hex): string
    {
        if (! self::isHexColor($hex)) {
            return '#ffffff';
        }

        $luminance = (0.299 * hexdec(substr($hex, 1, 2)) + 0.587 * hexdec(substr($hex, 3, 2)) + 0.114 * hexdec(substr($hex, 5, 2))) / 255;

        return $luminance > 0.6 ? '#333333' : '#ffffff';
    }
}
