<?php

namespace App\Actions\Student\Guardian;

use App\Actions\Settings\EmailTemplate\CreateDefaultsAction;
use App\Helpers\Facades\WhatsappHelper;
use App\Mail\AppointmentMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Guardian;
use App\Services\EmailTemplateRenderer;
use App\Services\Student\ParentMailData;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Give a parent a one-time link to choose their portal password — the invite for
 * a new parent and the reset for one who forgot it.
 *
 * The raw token only exists in the link; the guardian row keeps its sha256, so a
 * database read cannot be turned into a login. A new link replaces any earlier one.
 *
 * A parent who never set a password gets the "Parent Portal Invite" email, anyone
 * else the "Parent Password Reset". If the school has no template for that event
 * yet, the starter wording is created first; one the school switched off is
 * respected and nothing is emailed.
 *
 * Delivery is best effort and never blocks the link: the email is sent right away
 * (not queued) so the result says whether it really went, and WhatsApp follows
 * when configured. The link itself is returned so staff can share it by hand when
 * neither channel reaches the parent.
 *
 * The link opens the parent_portal app at the address in Settings → Student
 * Settings (or PARENT_PORTAL_URL); with neither set there is nowhere to send it.
 */
class SendInviteAction
{
    public const VALID_DAYS = 7;

    public function execute(int $guardianId, ?int $userId = null): array
    {
        try {
            $guardian = Guardian::find($guardianId);
            if (! $guardian) {
                throw new Exception("Parent not found with the specified ID: $guardianId.", 1);
            }
            if (! $guardian->isActive()) {
                throw new Exception('This parent\'s login is disabled.', 1);
            }

            $portal = StudentSettings::current();
            if (! $portal->portalLink()) {
                throw new Exception('Add the parent portal address in Settings → Student Settings first, so the link has somewhere to open.', 1);
            }

            // Decided before the token is saved: saving never touches the password.
            $type = ParentMailData::typeFor($guardian);

            $token = Str::random(48);
            $expiresAt = now()->addDays(self::VALID_DAYS);
            $guardian->forceFill([
                'invite_token_hash' => hash('sha256', $token),
                'invite_expires_at' => $expiresAt,
                'invited_at' => now(),
            ])->save();

            $link = $portal->portalLink('set-password/'.$token);
            $sent = [];
            $problems = [];

            if (! EmailTemplate::where('module', ParentMailData::MODULE)->where('type', $type)->exists()) {
                (new CreateDefaultsAction())->execute(ParentMailData::MODULE, $userId);
            }

            $rendered = null;
            try {
                $rendered = app(EmailTemplateRenderer::class)->render(
                    ParentMailData::MODULE,
                    $type,
                    app(ParentMailData::class)->forLink($guardian, $type, $link, $expiresAt),
                );
            } catch (\Throwable $th) {
                $problems[] = 'the "'.EmailTemplate::typeLabelFor(ParentMailData::MODULE, $type).'" email template is switched off in Settings → Email Templates';
            }

            if ($rendered && filled($guardian->email)) {
                $log = EmailLog::create([
                    'tenant_id' => $guardian->tenant_id,
                    'module' => ParentMailData::MODULE,
                    'type' => $type,
                    'related_type' => $guardian->getMorphClass(),
                    'related_id' => $guardian->id,
                    'email_template_id' => $rendered['template']->id,
                    'to_email' => $guardian->email,
                    'reply_to' => $rendered['reply_to'],
                    'subject' => $rendered['subject'],
                    'body' => $rendered['body'],
                    'status' => 'queued',
                    'created_by' => $userId,
                ]);
                try {
                    // LogSentEmail flips the row to "sent" once the message is out.
                    Mail::to($guardian->email)->send(new AppointmentMail(
                        subjectLine: $rendered['subject'],
                        bodyHtml: $rendered['body'],
                        replyToAddress: $rendered['reply_to'],
                        companyName: tenant_cache('company_name', '') ?? '',
                        logId: $log->id,
                        footerNote: $rendered['footer_note'],
                    ));
                    $sent[] = 'email to '.$guardian->email;
                } catch (\Throwable $th) {
                    $log->update(['status' => 'failed', 'error' => $th->getMessage()]);
                    Log::warning('Parent set-password email failed', ['guardian' => $guardian->id, 'error' => $th->getMessage()]);
                    $problems[] = 'the email to '.$guardian->email.' could not be sent ('.Str::limit($th->getMessage(), 120).')';
                }
            } elseif ($rendered) {
                $problems[] = 'the parent has no email address';
            }

            if ($rendered && config('services.meta_whatsapp.access_token')) {
                try {
                    WhatsappHelper::sendMessage($guardian->mobile, $rendered['subject']."\n".$link);
                    $sent[] = 'WhatsApp to '.$guardian->mobile;
                } catch (\Throwable $th) {
                    Log::warning('Parent invite WhatsApp failed', ['guardian' => $guardian->id, 'error' => $th->getMessage()]);
                }
            }

            $return['success'] = true;
            $return['message'] = match (true) {
                ! $sent => 'Link created, but '.implode(' and ', $problems).'. Share the link below with the parent.',
                (bool) $problems => 'Set-password link sent by '.implode(' and ', $sent).', but '.implode(' and ', $problems).'.',
                default => 'Set-password link sent by '.implode(' and ', $sent).'.',
            };
            $return['data'] = ['guardian' => $guardian, 'link' => $link, 'expires_at' => $expiresAt, 'type' => $type, 'delivered' => (bool) $sent];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
