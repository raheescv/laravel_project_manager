<?php

namespace App\Support;

use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;

/**
 * Bilingual clauses printed under the inventory table of a booking's Unit
 * Handover & Snagging checklist — warranty terms, exclusions, anything else the
 * handover is signed against.
 *
 * They live on the booking (rent_outs.handover_terms), not in settings: two
 * handovers signed the same month can carry different wording, the same way
 * Agreement Points already do. A clause is a heading plus a rich-text (HTML)
 * body in each language, edited with <x-rich-text-editor> on the Checklist tab
 * and sanitised through App\Support\RichText on the way in and out.
 *
 * Clause numbers are NOT part of the wording — they are rendered from the
 * clause's position, Latin digits on the English side and Arabic-Indic on the
 * Arabic side, so inserting a clause never means renumbering the rest by hand.
 *
 * Only a lease / sale prints them: a rental hands the unit back rather than
 * handing it over, and its checklist carries the Move-Out block instead.
 *
 * There is deliberately no wording in this class. Starter clauses live in
 * resources/data/rent-out-handover-terms.sample.json and reach a booking only
 * when somebody loads them on the tab and saves.
 *
 * @see \App\Support\RentOutChecklistNotes for the declaration above the signatures
 */
class RentOutHandoverTerms
{
    /** Printed above the clauses when the booking left the English heading empty. */
    public const FALLBACK_HEADING = 'Handover Terms';

    private const SAMPLE_FILE = 'data/rent-out-handover-terms.sample.json';

    /** The shape stored on a booking that has no terms. */
    public static function blank(): array
    {
        return [
            'heading_en' => '',
            'heading_ar' => '',
            'clauses' => [],
        ];
    }

    /** A blank clause row, as the tab adds it. */
    public static function emptyClause(): array
    {
        return ['title_en' => '', 'title_ar' => '', 'body_en' => '', 'body_ar' => ''];
    }

    /** What the booking has stored, normalised. */
    public static function of(?RentOut $rentOut): array
    {
        $stored = $rentOut?->handover_terms;

        return self::normalize(is_array($stored) ? $stored : []);
    }

    public static function saveFor(RentOut $rentOut, array $terms): array
    {
        $normalized = self::normalize($terms);

        // Nothing written at all is stored as null, so an untouched booking
        // stays indistinguishable from one that existed before this column did.
        $rentOut->update([
            'handover_terms' => $normalized['clauses'] === [] && $normalized['heading_en'] === '' && $normalized['heading_ar'] === ''
                ? null
                : $normalized,
        ]);

        return $normalized;
    }

    /**
     * Starter clauses shipped as data, offered behind a button on the tab. They
     * are never printed until they have been loaded onto a booking and saved.
     */
    public static function sample(): array
    {
        $path = resource_path(self::SAMPLE_FILE);
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return self::normalize(is_array($data) ? $data : []);
    }

    /**
     * Headings stay plain text; bodies are rich text, so they are sanitised
     * rather than trimmed — an editor left "empty" still posts markup like
     * <p><br></p>. A clause with nothing in any of its four fields is dropped,
     * which is how the tab's blank row disappears on save.
     */
    public static function normalize(array $terms): array
    {
        $clauses = [];

        foreach ($terms['clauses'] ?? [] as $clause) {
            $clause = is_array($clause) ? $clause : [];

            $normalized = [
                'title_en' => self::plain($clause['title_en'] ?? ''),
                'title_ar' => self::plain($clause['title_ar'] ?? ''),
                'body_en' => self::rich($clause['body_en'] ?? ''),
                'body_ar' => self::rich($clause['body_ar'] ?? ''),
            ];

            if (implode('', $normalized) !== '') {
                $clauses[] = $normalized;
            }
        }

        return [
            'heading_en' => self::plain($terms['heading_en'] ?? ''),
            'heading_ar' => self::plain($terms['heading_ar'] ?? ''),
            'clauses' => $clauses,
        ];
    }

    /** True when this booking's checklist is a handover rather than a tenancy. */
    public static function appliesTo(?RentOut $rentOut): bool
    {
        return $rentOut?->agreement_type !== AgreementType::Rental;
    }

    /**
     * Print-ready block, or [] when there is nothing to print for this booking:
     * ['heading_en' => ..., 'heading_ar' => ..., 'has_arabic' => bool,
     *  'clauses' => [['no_en' => '1.', 'no_ar' => '١.', 'title_en' => ..., 'body_en' => <html>, ...]]].
     */
    public static function forPrint(?RentOut $rentOut): array
    {
        if (! self::appliesTo($rentOut)) {
            return [];
        }

        $terms = self::of($rentOut);
        if ($terms['clauses'] === []) {
            return [];
        }

        $clauses = [];
        foreach ($terms['clauses'] as $index => $clause) {
            $number = $index + 1;
            $clauses[] = [
                'no_en' => $number.'.',
                'no_ar' => self::arabicDigits($number).'.',
                'title_en' => $clause['title_en'],
                'title_ar' => $clause['title_ar'],
                'body_en' => RichText::toHtml($clause['body_en']),
                'body_ar' => RichText::toHtml($clause['body_ar']),
            ];
        }

        // A booking that never filled the Arabic side gets the full width for
        // English instead of a column of empty cells.
        $hasArabic = (bool) array_filter(
            $clauses,
            fn ($clause) => $clause['title_ar'] !== '' || $clause['body_ar'] !== ''
        );

        return [
            'heading_en' => $terms['heading_en'] ?: self::FALLBACK_HEADING,
            'heading_ar' => $terms['heading_ar'],
            'has_arabic' => $hasArabic,
            'clauses' => $clauses,
        ];
    }

    /** 12 → ١٢, so the Arabic column numbers its clauses the way it reads them. */
    public static function arabicDigits(int|string $value): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            (string) $value
        );
    }

    /** Headings are plain text — entities are decoded so "&amp;" prints as "&". */
    private static function plain(mixed $value): string
    {
        return trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function rich(mixed $value): string
    {
        $value = (string) $value;

        return RichText::isBlank($value) ? '' : RichText::sanitize($value);
    }
}
