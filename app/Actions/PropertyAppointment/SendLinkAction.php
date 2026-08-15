<?php

namespace App\Actions\PropertyAppointment;

use App\Jobs\PropertyAppointment\SendAppointmentEmailJob;
use App\Models\EmailLog;
use App\Models\PropertyAppointment;
use App\Services\EmailTemplateRenderer;
use App\Services\PropertyAppointment\AppointmentMailData;
use Illuminate\Support\Str;

/**
 * Queues one lifecycle email for a appointment and records it in the delivery log
 * so staff can see whether the customer actually received the link.
 */
class SendLinkAction
{
    public function execute($id, $type, $userId, $refreshToken = false)
    {
        try {
            $model = PropertyAppointment::with('customer')->findOrFail($id);

            $email = $model->customer?->email;
            if (blank($email)) {
                throw new \Exception('This customer has no email address on file. Add one to their account before sending the link.', 1);
            }

            // Fail fast if the tenant has not activated a template for this
            // event — better a clear error now than a silent non-delivery.
            $rendered = app(EmailTemplateRenderer::class)->render(
                AppointmentMailData::MODULE,
                $type,
                app(AppointmentMailData::class)->forAppointment($model)
            );

            if ($refreshToken) {
                $model->token = (string) Str::uuid();
            }

            $model->link_sent_at = now();
            $model->updated_by = $userId;
            $model->save();

            $log = EmailLog::create([
                'tenant_id' => $model->tenant_id,
                'module' => AppointmentMailData::MODULE,
                'type' => $type,
                'related_type' => $model->getMorphClass(),
                'related_id' => $model->id,
                'email_template_id' => $rendered['template']->id,
                'to_email' => $email,
                'reply_to' => $rendered['reply_to'],
                // Snapshot what we are about to send. The job overwrites this
                // with the final render, but storing it now means a send that
                // never runs is still inspectable.
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'status' => 'queued',
                'created_by' => $userId,
            ]);

            SendAppointmentEmailJob::dispatch($model->tenant_id, $model->id, $type, $log->id);

            $return['success'] = true;
            $return['message'] = 'Appointment link queued for '.$email;
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
