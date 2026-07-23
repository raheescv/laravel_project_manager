<?php

namespace App\Traits;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Shared expiry presentation for customer KYC documents (ID, passport, CR, CP,
 * EID, tax card). Used by the customer view tabs so the same date renders the
 * same badge everywhere.
 */
trait HasDocumentExpiryState
{
    /**
     * @param  string|\DateTimeInterface|null  $date
     * @return array{state: string, class: string, label: string, days: int|null}
     */
    protected function expiryState($date, int $warnWithinDays = 60): array
    {
        $none = ['state' => 'none', 'class' => 'mute', 'label' => 'Not set', 'days' => null];

        if (empty($date)) {
            return $none;
        }

        try {
            $expiry = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return $none;
        }

        $days = (int) now()->startOfDay()->diffInDays($expiry, false);

        if ($days < 0) {
            return [
                'state' => 'gone',
                'class' => 'bad',
                'label' => 'Expired '.abs($days).' '.(abs($days) === 1 ? 'day' : 'days').' ago',
                'days' => $days,
            ];
        }

        if ($days <= $warnWithinDays) {
            return [
                'state' => 'soon',
                'class' => 'warn',
                'label' => $days === 0 ? 'Expires today' : 'Expires in '.$days.' '.($days === 1 ? 'day' : 'days'),
                'days' => $days,
            ];
        }

        return [
            'state' => 'fine',
            'class' => 'ok',
            'label' => now()->startOfDay()->diffForHumans($expiry, [
                'parts' => 2,
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]).' left',
            'days' => $days,
        ];
    }
}
