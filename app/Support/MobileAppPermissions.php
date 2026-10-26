<?php

namespace App\Support;

/**
 * The abilities the mobile app (mobileApp/) gates a screen or action on.
 *
 * Mirrors `PermissionSlug` in mobileApp/lib/shared/domain/constants/mobile_permissions.dart
 * plus every `EnsureMobilePermission` gate in routes/api_v1.php, so the role
 * editor can show a role's mobile access on its own. Add a name here whenever
 * the app or the v1 API starts checking a new one.
 */
class MobileAppPermissions
{
    /** @var list<string> */
    public const NAMES = [
        'sale.create',
        'sale.view',
        'sale.edit',
        'sale.delete',
        'sale.mobile change employee',
        'sales return.view',
        'sales return.create',
        'sales return.edit',
        'report.sale item',
        'day session.create',
        'day session.print',
        'inventory.stock check',
        'student card.assign',
        'configuration.settings',
        'token.issue',
    ];

    public static function includes(string $name): bool
    {
        return in_array($name, self::NAMES, true);
    }
}
