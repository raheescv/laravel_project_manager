<?php

namespace App\Console\Commands\Property;

use App\Actions\PropertyAppointment\NotifyAction;
use App\Models\PropertyAppointment;
use App\Models\Tenant;
use App\Services\EmailTemplateRenderer;
use App\Services\PropertyAppointment\AppointmentMailData;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * Queues the reminder email for appointments coming up inside the reminder window.
 *
 * Safe to run as often as the schedule likes: each appointment is stamped with
 * reminder_sent_at when its reminder is queued, so nobody is reminded twice.
 * Rescheduling clears that stamp, so the new time gets its own reminder.
 */
class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'appointments:send-reminders {--hours= : How many hours ahead to look, overriding config}';

    protected $description = 'Emails customers a reminder before their confirmed property appointment';

    public function handle(TenantService $tenantService): int
    {
        $hours = (int) ($this->option('hours') ?: config('property_appointment.reminder_hours_before', 24));

        if ($hours <= 0) {
            $this->info('Appointment reminders are switched off (property_appointment.reminder_hours_before = 0).');

            return self::SUCCESS;
        }

        // This runs outside a request, so there is no tenant to scope by. Read
        // across every tenant deliberately, then pin each one before sending —
        // the template lookup and the customer relation are both tenant-scoped.
        $due = PropertyAppointment::withoutGlobalScopes()
            ->where('status', 'scheduled')
            // ->whereNull('reminder_sent_at')
            // ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [now(), now()->addHours($hours)])
            ->orderBy('scheduled_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No appointments are due a reminder.');

            return self::SUCCESS;
        }

        $queued = 0;
        $skipped = 0;

        foreach ($due->groupBy('tenant_id') as $tenantId => $appointments) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                continue;
            }

            $tenantService->setCurrentTenant($tenant);

            // Checked once per tenant. A tenant with no active reminder template
            // has opted out, so their rows are left untouched and start working
            // the moment they activate one — rather than collecting a failed log
            // entry every single hour.
            try {
                app(EmailTemplateRenderer::class)->activeTemplate(AppointmentMailData::MODULE, 'appointment_reminder');
            } catch (\Throwable) {
                $skipped += $appointments->count();
                $this->line("· {$tenant->name}: no active reminder template — skipped {$appointments->count()}");

                continue;
            }

            foreach ($appointments as $appointment) {
                $response = (new NotifyAction())->execute($appointment, 'appointment_reminder');

                // Stamped whatever the outcome: a customer with no email address
                // will not gain one within the hour, and retrying would only
                // fill the delivery log with the same failure.
                $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();

                if ($response['success']) {
                    $queued++;
                } else {
                    $skipped++;
                    $this->line("· {$appointment->reference_no}: {$response['message']}");
                }
            }
        }

        $this->info("Appointment reminders queued: {$queued}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
