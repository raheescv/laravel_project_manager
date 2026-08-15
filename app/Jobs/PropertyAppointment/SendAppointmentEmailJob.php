<?php

namespace App\Jobs\PropertyAppointment;

use App\Mail\AppointmentMail;
use App\Models\EmailLog;
use App\Models\PropertyAppointment;
use App\Models\Tenant;
use App\Services\EmailTemplateRenderer;
use App\Services\PropertyAppointment\AppointmentMailData;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAppointmentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300];

    public $timeout = 120;

    /**
     * tenant_id is passed explicitly: a queued job runs outside the request, so
     * TenantService cannot resolve a tenant from the subdomain or the session
     * and every scoped query would otherwise read across all tenants.
     */
    public function __construct(
        public int $tenantId,
        public int $appointmentId,
        public string $type,
        public int $logId,
    ) {}

    public function handle(TenantService $tenantService, EmailTemplateRenderer $renderer): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }
        $tenantService->setCurrentTenant($tenant);

        $log = EmailLog::find($this->logId);
        $appointment = PropertyAppointment::with(['customer', 'salesman', 'rentOut.property'])->find($this->appointmentId);

        if (! $appointment || ! $log) {
            return;
        }

        $email = $appointment->customer?->email;

        if (blank($email)) {
            $log?->update(['status' => 'failed', 'error' => 'The customer has no email address on file.']);

            return;
        }

        try {
            $rendered = $renderer->render(
                AppointmentMailData::MODULE,
                $this->type,
                app(AppointmentMailData::class)->forAppointment($appointment)
            );

            Mail::to($email)->send(new AppointmentMail(
                subjectLine: $rendered['subject'],
                bodyHtml: $rendered['body'],
                replyToAddress: $rendered['reply_to'],
                companyName: tenant_cache('company_name', '') ?? '',
                logId: $log->id,
            ));

            // The row is flipped to "sent" by LogSentEmail, which reads the
            // real MIME body off the delivered message — closer to what the
            // customer actually received than anything re-rendered here.
        } catch (\Throwable $th) {
            $log->update(['status' => 'failed', 'error' => $th->getMessage()]);

            throw $th;
        }
    }

    public function failed(\Throwable $exception): void
    {
        EmailLog::where('id', $this->logId)
            ->update(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}
