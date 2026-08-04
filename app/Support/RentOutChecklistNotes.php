<?php

namespace App\Support;

use App\Enums\RentOut\AgreementType;
use App\Models\Configuration;
use App\Models\RentOut;

/**
 * Editable heading + declaration printed above the signature blocks of the
 * Unit Handover & Snagging checklist — one note per agreement type: the Move-In
 * block for a rental, the Handover block for a lease/sale.
 *
 * The declaration is rich text (HTML) edited with <x-rich-text-editor>, so a
 * handover note can carry headings, numbered clauses, bullet lists and RTL
 * (Arabic) paragraphs. Everything is sanitised through App\Support\RichText on
 * the way in and rendered as HTML on the way out; a note saved as plain text
 * before this became rich text still prints exactly as it did.
 *
 * A rental also prints a Move-Out block; that one stays fixed (see MOVE_OUT_NOTE)
 * because it is a hand-back statement, not a receipt of the unit.
 *
 * Stored as a single JSON configuration key; anything missing falls back to the
 * defaults below, so an untouched tenant prints exactly what it printed before.
 */
class RentOutChecklistNotes
{
    public const CONFIG_KEY = 'rent_out_checklist_notes';

    /** Fixed Move-Out declaration — rentals only, not configurable. */
    private const MOVE_OUT_NOTE = [
        'title' => 'To be accomplished during Move-Out',
        'declaration' => 'We hereby confirm that the above mentioned items were physically checked and received from {tenant_name}. The Lessee confirms no further claim once the access card/s are returned.',
    ];

    /** Receipt-of-unit sentence shared by both agreement types. */
    private const RECEIPT_DECLARATION = '<p>I, {tenant_name}, hereby confirm that the above mentioned furniture, kitchen appliances &amp; accessories were physically checked and received by me in good condition.</p>';

    /**
     * The bare minimum a tenant prints before touching the settings. Anything
     * beyond it — warranty clauses, Arabic wording, house rules — belongs in the
     * saved configuration, edited in Settings → Rent Out Settings → Checklist Notes,
     * never in here.
     */
    public static function defaults(): array
    {
        return [
            'rental' => [
                'title' => 'To be accomplished during Move-In',
                'declaration' => self::RECEIPT_DECLARATION,
            ],
            'lease' => [
                'title' => 'To be accomplished during Handover',
                'declaration' => self::RECEIPT_DECLARATION,
            ],
        ];
    }

    /** Tokens offered in the settings UI, with a short explanation each. */
    public static function tokenHelp(): array
    {
        return [
            '{tenant_name}' => 'Tenant / owner name',
            '{property}' => 'Property / unit number',
            '{building}' => 'Building name',
            '{group}' => 'Group / project name',
            '{type}' => 'Property type',
            '{company}' => 'Company name',
            '{today}' => "Today's date",
        ];
    }

    /** Stored notes merged over the defaults. */
    public static function all(): array
    {
        $stored = json_decode((string) Configuration::where('key', self::CONFIG_KEY)->value('value'), true);
        $stored = is_array($stored) ? $stored : [];

        return self::normalize($stored);
    }

    public static function save(array $notes): void
    {
        Configuration::updateOrCreate(
            ['key' => self::CONFIG_KEY],
            ['value' => json_encode(self::normalize($notes), JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * Fill in whatever is missing from the defaults. Titles stay plain text;
     * declarations are rich text, so they are sanitised rather than trimmed —
     * an editor left "empty" still posts markup like <p><br></p>.
     */
    public static function normalize(array $notes): array
    {
        $normalized = [];

        foreach (self::defaults() as $typeKey => $default) {
            $title = trim(strip_tags((string) ($notes[$typeKey]['title'] ?? '')));
            $normalized[$typeKey]['title'] = $title !== '' ? $title : $default['title'];

            $declaration = (string) ($notes[$typeKey]['declaration'] ?? '');
            $normalized[$typeKey]['declaration'] = RichText::isBlank($declaration)
                ? $default['declaration']
                : RichText::sanitize($declaration);
        }

        return $normalized;
    }

    /**
     * Blocks to print for a booking: ['move_in' => ['label' => ..., 'decl' => ...], ...]
     * with every token already replaced. `decl` is print-ready HTML; rentals also
     * get the fixed Move-Out block.
     */
    public static function phasesFor(?RentOut $rentOut): array
    {
        $isRental = $rentOut?->agreement_type === AgreementType::Rental;
        $note = self::all()[$isRental ? 'rental' : 'lease'];
        $tokens = self::tokens($rentOut);

        $resolved = [
            'move_in' => [
                'label' => strtr($note['title'], self::plainTokens($tokens)),
                'decl' => strtr(RichText::toHtml($note['declaration']), $tokens),
            ],
        ];

        if ($isRental) {
            $resolved['move_out'] = [
                'label' => self::MOVE_OUT_NOTE['title'],
                'decl' => strtr(RichText::toHtml(self::MOVE_OUT_NOTE['declaration']), $tokens),
            ];
        }

        return $resolved;
    }

    /**
     * Token values escaped for an HTML body. Public because every other block
     * printed on the same checklist — the handover terms, for one — has to
     * resolve the same tokens against the same booking.
     */
    public static function tokens(?RentOut $rentOut): array
    {
        return array_map(fn ($value) => e($value), self::rawTokens($rentOut));
    }

    /** Token values for plain-text contexts (headings). */
    public static function plainTokens(array $tokens): array
    {
        return array_map(fn ($value) => html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $tokens);
    }

    private static function rawTokens(?RentOut $rentOut): array
    {
        return [
            '{tenant_name}' => $rentOut?->account?->name ?: 'the Lessee',
            '{property}' => $rentOut?->property?->number ?: '',
            '{building}' => $rentOut?->building?->name ?: '',
            '{group}' => $rentOut?->group?->name ?: '',
            '{type}' => $rentOut?->type?->name ?: '',
            '{company}' => (string) Configuration::where('key', 'company_name')->value('value'),
            '{today}' => now()->format('d M Y'),
        ];
    }
}
