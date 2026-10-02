<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;

/**
 * A ticket's group is free text, so "sales", "Sales " and "SALES" must land on
 * the one spelling already in use or the filter strip splits into duplicates.
 */
class TicketGroupName
{
    public static function normalize(?string $group): ?string
    {
        $group = trim(preg_replace('/\s+/', ' ', (string) $group));
        if ($group === '') {
            return null;
        }

        $existing = Ticket::query()->whereRaw('LOWER(`group`) = ?', [mb_strtolower($group)])->value('group');

        return $existing ?? $group;
    }
}
