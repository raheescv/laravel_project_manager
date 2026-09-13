<?php

namespace App\Support;

/**
 * Pipeline view of the statuses in leadStatuses(): which lead board stage each
 * one belongs to, the Bootstrap tone it wears, and which real status a stored
 * value means.
 *
 * Stored values drift from that list ("Low Budget " with a trailing space,
 * "Dead lead", typos such as "Fallow Up"), so reads go through canonical()
 * instead of comparing strings directly.
 */
class LeadPipeline
{
    /** Board column for stored values that match no real status. */
    public const UNMAPPED = 'Unmapped';

    /** Stages whose leads are finished, won or lost. */
    public const CLOSED_STAGES = ['won', 'lost'];

    private const STAGES = [
        'new' => ['name' => 'New', 'icon' => 'fa-inbox', 'tone' => 'primary', 'statuses' => ['New Lead']],
        'contacting' => ['name' => 'Contacting', 'icon' => 'fa-phone', 'tone' => 'info', 'statuses' => ['Follow Up', 'Call Back', 'Same Day Call Back', 'No Answer', 'Whatsapp Only', 'Shopping For Info']],
        'qualified' => ['name' => 'Qualified', 'icon' => 'fa-star', 'tone' => 'warning', 'statuses' => ['Interested', 'Follow Up For Visit', 'Visit Scheduled']],
        'won' => ['name' => 'Won', 'icon' => 'fa-trophy', 'tone' => 'success', 'statuses' => ['Closed Deal']],
        'lost' => ['name' => 'Lost', 'icon' => 'fa-ban', 'tone' => 'secondary', 'statuses' => ['Low Budget', 'Not Interested', 'Dead Lead', 'Rejected', 'Drop']],
    ];

    /**
     * Stages in board order, each listing only statuses leadStatuses() still
     * ships. A status added there later lands in "Contacting" until it is placed.
     *
     * @return array<string, array{name: string, icon: string, tone: string, statuses: list<string>}>
     */
    public static function stages(): array
    {
        $known = array_keys(leadStatuses());

        $stages = array_map(
            fn (array $stage) => [...$stage, 'statuses' => array_values(array_intersect($stage['statuses'], $known))],
            self::STAGES
        );

        $placed = array_merge(...array_column($stages, 'statuses'));
        array_push($stages['contacting']['statuses'], ...array_values(array_diff($known, $placed)));

        return $stages;
    }

    /** @return list<string> every real status, in board order */
    public static function statuses(): array
    {
        return array_merge(...array_column(self::stages(), 'statuses'));
    }

    /** @return list<string> statuses in the won and lost stages */
    public static function closedStatuses(): array
    {
        $stages = self::stages();

        return array_merge(...array_map(fn (string $key) => $stages[$key]['statuses'], self::CLOSED_STAGES));
    }

    /** The real status a stored value means, or UNMAPPED when it matches none. */
    public static function canonical(?string $stored): string
    {
        $needle = mb_strtolower(trim((string) $stored));

        foreach (self::statuses() as $status) {
            if (mb_strtolower($status) === $needle) {
                return $status;
            }
        }

        return self::UNMAPPED;
    }

    /** Stage key for a real status, or null for UNMAPPED. */
    public static function stageOf(string $status): ?string
    {
        foreach (self::stages() as $key => $stage) {
            if (in_array($status, $stage['statuses'], true)) {
                return $key;
            }
        }

        return null;
    }

    /** Whether a lead in this status is still being worked (unknown statuses count as open). */
    public static function isOpen(string $status): bool
    {
        return ! in_array(self::stageOf($status), self::CLOSED_STAGES, true);
    }

    /** Bootstrap tone for a status, read from leadStatusBadgeClass() so the board matches list badges. */
    public static function tone(string $status): string
    {
        if ($status === self::UNMAPPED) {
            return 'danger';
        }

        preg_match('/\bbg-(primary|secondary|success|info|warning|danger)\b/', leadStatusBadgeClass($status), $match);

        return $match[1] ?? 'secondary';
    }

    /** Font Awesome 4.3 icon for a lead source. */
    public static function sourceIcon(?string $source): string
    {
        return match ($source) {
            'Social Media' => 'fa-share-alt',
            'Facebook' => 'fa-facebook',
            'Instagram' => 'fa-camera-retro',
            'Snapchat' => 'fa-comment-o',
            'YouTube' => 'fa-youtube-play',
            'SMS' => 'fa-comment',
            'E-mail Campaign' => 'fa-envelope',
            'Outdoor Marketing' => 'fa-bullhorn',
            'Personal' => 'fa-user',
            'Walk-In' => 'fa-male',
            'Local Broker' => 'fa-briefcase',
            'International Broker' => 'fa-globe',
            default => 'fa-circle-o',
        };
    }

    /** Up to two initials for an avatar chip. */
    public static function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        return mb_strtoupper(implode('', array_map(fn (string $word) => mb_substr($word, 0, 1), array_slice($words, 0, 2))));
    }

    /** Stable avatar hue (0-359) for a name. */
    public static function hue(?string $name): int
    {
        return crc32((string) $name) % 360;
    }
}
