<?php

namespace App\Services\Student;

use App\Models\Guardian;
use App\Services\EmailTemplateRenderer;
use App\Support\EmailStyler;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;

/**
 * Merge-variable values for parent portal emails (see config/email_templates.php).
 */
class ParentMailData
{
    public const MODULE = 'student_portal';

    public const INVITE = 'parent_invite';

    public const PASSWORD_RESET = 'parent_password_reset';

    /** Which email a set-password link goes out as: first login, or a reset. */
    public static function typeFor(Guardian $guardian): string
    {
        return $guardian->isPendingInvite() ? self::INVITE : self::PASSWORD_RESET;
    }

    /** @return array<string, string> */
    public function forLink(Guardian $guardian, string $type, string $link, Carbon $expiresAt): array
    {
        return [
            'company_name' => tenant_cache('company_name', '') ?: config('app.name'),
            'parent_name' => $guardian->name,
            'parent_mobile' => $guardian->mobile,
            'student_names' => $guardian->students()->orderBy('accounts.name')->pluck('accounts.name')->join(', ', ' and '),
            'set_password_link' => $link,
            'set_password_button' => EmailStyler::button($link, self::buttonLabel($type), EmailTemplateRenderer::accent()),
            'link_expires_at' => $expiresAt->format('d M Y'),
        ];
    }

    public function sample(?string $type = null): array
    {
        $link = StudentSettings::current()->portalLink('set-password/sample-token') ?? 'https://parents.example.com/#/set-password/sample-token';

        return [
            'company_name' => tenant_cache('company_name', '') ?: config('app.name'),
            'parent_name' => 'Ahmed Saleh',
            'parent_mobile' => '55123456',
            'student_names' => 'Sara and Omar',
            'set_password_link' => $link,
            'set_password_button' => EmailStyler::button($link, self::buttonLabel($type), EmailTemplateRenderer::accent()),
            'link_expires_at' => now()->addDays(7)->format('d M Y'),
        ];
    }

    private static function buttonLabel(?string $type): string
    {
        return $type === self::PASSWORD_RESET ? 'Choose a new password' : 'Set your password';
    }
}
