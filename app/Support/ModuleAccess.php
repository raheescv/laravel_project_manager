<?php

namespace App\Support;

use App\Models\Configuration;
use Closure;
use Illuminate\Support\Str;

/**
 * Which feature modules (config/modules.php) the current tenant runs.
 *
 * A tenant picks one system type in Settings → Module Configuration
 * (`active_module`). Most modules are part of the base app and stay on when no
 * system has been chosen, as they always have. OPT_IN modules are different:
 * they exist only inside a system that lists them, so a tenant that never chose
 * that system never sees them — the School module (student cards, the parent
 * portal, QPay top-ups) is one.
 *
 * Read from `configurations` on each call: one indexed query, and a switch of
 * system must take effect on the very next request.
 */
final class ModuleAccess
{
    public const SCHOOL = 'school';

    public const EMPLOYEE_COMMISSION = 'employee_commission';

    /** Modules that are off unless the active system includes them. */
    public const OPT_IN = [self::SCHOOL];

    public static function activeSystem(): ?string
    {
        $system = Configuration::where('key', 'active_module')->value('value');

        return filled($system) && array_key_exists($system, config('modules.systems', [])) ? $system : null;
    }

    public static function enabled(string $module): bool
    {
        $system = self::activeSystem();

        if (! $system) {
            return ! in_array($module, self::OPT_IN, true);
        }

        return in_array($module, config("modules.systems.{$system}", []), true);
    }

    /** Student cards, the parent portal and QPay top-ups. */
    public static function school(): bool
    {
        return self::enabled(self::SCHOOL);
    }

    /**
     * Permission groups and exact names owned by the modules of one system.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    public static function systemPermissions(string $system): array
    {
        $groups = [];
        $exact = [];
        foreach (config("modules.systems.{$system}", []) as $module) {
            foreach (config("modules.modules.{$module}.permissions", []) as $permission) {
                str_contains($permission, '.') ? $exact[] = $permission : $groups[] = $permission;
            }
        }

        return [$groups, $exact];
    }

    /**
     * Whether a permission belongs to the tenant's system: the rule Settings →
     * Roles uses to choose which abilities it lists. A permission a role still
     * holds from another system can't be seen or unticked there, so it must not
     * switch anything on either. Build once and reuse: it reads the active
     * system a single time.
     *
     * @return Closure(string): bool
     */
    public static function permissionFilter(): Closure
    {
        $system = self::activeSystem();
        [$groups, $exact] = $system ? self::systemPermissions($system) : self::disabledOptInPermissions();
        $listed = fn (string $permission): bool => in_array($permission, $exact, true) || in_array(Str::before($permission, '.'), $groups, true);

        return $system ? $listed : fn (string $permission): bool => ! $listed($permission);
    }

    /**
     * Permission groups and exact names that belong to OPT_IN modules the tenant
     * does not run — to be hidden from role editing.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    public static function disabledOptInPermissions(): array
    {
        $groups = [];
        $exact = [];
        foreach (self::OPT_IN as $module) {
            if (self::enabled($module)) {
                continue;
            }
            foreach (config("modules.modules.{$module}.permissions", []) as $permission) {
                str_contains($permission, '.') ? $exact[] = $permission : $groups[] = $permission;
            }
        }

        return [$groups, $exact];
    }
}
