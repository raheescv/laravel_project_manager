<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Services\TenantService;
use Illuminate\Mail\Events\MessageSent;

/**
 * Records EVERY outbound email, whoever sent it.
 *
 * Hooking the framework's mail event rather than each call site means any
 * future Mail::send anywhere in the application is logged automatically —
 * a caller cannot forget to log, because logging is not the caller's job.
 *
 * Mailables that already created a queued row (see SendLinkAction) carry its
 * id in a header, so this updates that row instead of writing a duplicate.
 */
class LogSentEmail
{
    /** Header used to tie a message back to a row created before sending. */
    public const LOG_HEADER = 'X-Email-Log-Id';

    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $headers = $message->getHeaders();

            $subject = $message->getSubject() ?? '';
            $body = $message->getHtmlBody() ?: $message->getTextBody() ?: '';
            $to = collect($message->getTo() ?? [])->map(fn ($address) => $address->getAddress())->implode(', ');
            $replyTo = collect($message->getReplyTo() ?? [])->map(fn ($address) => $address->getAddress())->first();

            $logId = $headers->has(self::LOG_HEADER)
                ? (int) $headers->get(self::LOG_HEADER)->getBodyAsString()
                : null;

            if ($logId && ($log = EmailLog::withoutTenant()->find($logId))) {
                $log->update([
                    'status' => 'sent',
                    'subject' => $subject,
                    'body' => $body,
                    'to_email' => $to ?: $log->to_email,
                    'reply_to' => $replyTo ?: $log->reply_to,
                    'sent_at' => now(),
                    'error' => null,
                ]);

                return;
            }

            // Mail sent by anything that does not pre-log — still capture it.
            $tenantId = app(TenantService::class)->getCurrentTenantId();
            if (! $tenantId) {
                return;
            }

            EmailLog::create([
                'tenant_id' => $tenantId,
                'module' => 'general',
                'type' => 'general',
                'to_email' => $to,
                'reply_to' => $replyTo,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $th) {
            // Logging must never break an email that has already been delivered.
            report($th);
        }
    }
}
