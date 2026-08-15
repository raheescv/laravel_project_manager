<?php

namespace App\Actions\PropertyAppointment;

use App\Jobs\PropertyAppointment\SendAppointmentEmailJob;
use App\Models\EmailLog;
use App\Models\PropertyAppointment;
use App\Services\EmailTemplateRenderer;
use App\Services\PropertyAppointment\AppointmentMailData;

/**
 * Queues one lifecycle email about an appointment that has ALREADY changed.
 *
 * Two things separate this from SendLinkAction:
 *
 *  - It never fails its caller. A cancellation is not undone because the tenant
 *    deactivated the cancellation template, so everything here is caught and
 *    reported through the return value instead of thrown.
 *  - It leaves the booking token and link_sent_at alone. Nothing here invites
 *    the customer to book — it only tells them what happened.
 *
 * A send that cannot happen is still written to the email log as 'failed', so
 * "the customer was never told" is visible to staff rather than silent.
 */
class NotifyAction
{
    public function execute(PropertyAppointment $model, string $type, $userId = null): array
    {
        $email = $model->customer?->email;

        if (blank($email)) {
            return [
                'success' => false,
                'message' => 'The customer has no email address on file.',
            ];
        }

        try {
            $rendered = app(EmailTemplateRenderer::class)->render(
                AppointmentMailData::MODULE,
                $type,
                app(AppointmentMailData::class)->forAppointment($model)
            );
        } catch (\Throwable $th) {
            $this->log($model, $type, $email, $userId, 'failed', ['error' => $th->getMessage()]);

            return ['success' => false, 'message' => $th->getMessage()];
        }

        $log = $this->log($model, $type, $email, $userId, 'queued', [
            'email_template_id' => $rendered['template']->id,
            'reply_to' => $rendered['reply_to'],
            // Snapshot what we are about to send: a job that never runs is
            // still inspectable afterwards.
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
        ]);

        SendAppointmentEmailJob::dispatch($model->tenant_id, $model->id, $type, $log->id);

        return [
            'success' => true,
            'message' => 'Notification queued for '.$email,
            'data' => $log,
        ];
    }

    /** @param array<string, mixed> $extra */
    private function log(PropertyAppointment $model, string $type, string $email, $userId, string $status, array $extra = []): EmailLog
    {
        return EmailLog::create(array_merge([
            'tenant_id' => $model->tenant_id,
            'module' => AppointmentMailData::MODULE,
            'type' => $type,
            'related_type' => $model->getMorphClass(),
            'related_id' => $model->id,
            'to_email' => $email,
            'status' => $status,
            'created_by' => $userId,
        ], $extra));
    }
}
