<?php

namespace App\Services\PropertyAppointment;

use App\Models\PropertyAppointment;
use App\Services\EmailTemplateRenderer;
use App\Support\EmailStyler;

/**
 * Supplies the merge-variable VALUES for property-appointment emails.
 *
 * Keeping this beside the module rather than inside EmailTemplateRenderer is
 * what lets the renderer stay generic: the next module that wants templated
 * email adds its own data class and touches nothing here.
 */
class AppointmentMailData
{
    public const MODULE = 'property_appointment';

    /** @return array<string, string> */
    public function forAppointment(PropertyAppointment $appointment): array
    {
        $appointment->loadMissing(['rentOut.property', 'rentOut.building', 'rentOut.group', 'customer', 'employee']);

        return [
            'company_name' => tenant_cache('company_name', '') ?: config('app.name'),
            'customer_name' => $appointment->customer?->name ?? '',
            // property_name is the unit identifier on its own ("1508");
            // building_name and project_name give the address around it, so a
            // template can say "Unit 1508, MARINA TOWER" without hardcoding.
            'property_name' => $appointment->rentOut?->property?->number ?? '',
            'unit_number' => $appointment->rentOut?->property?->number ?? '',
            'building_name' => $appointment->rentOut?->building?->name ?? '',
            'project_name' => $appointment->rentOut?->group?->name ?? '',
            'employee_name' => $appointment->employee?->name ?? '',
            'employee_phone' => $appointment->employee?->mobile ?? '',
            'agreement_no' => '#'.$appointment->rent_out_id,
            'appointment_link' => route('property_appointment::public', $appointment->token),
            // A complete, styled call-to-action. Tenants drop this in and get
            // the Editorial button without writing any HTML themselves.
            'appointment_button' => EmailStyler::button(
                route('property_appointment::public', $appointment->token),
                'Choose your appointment time',
                EmailTemplateRenderer::accent()
            ),
            'link_expires_at' => $appointment->token_expires_at?->format('d M Y') ?? '',
            'appointment_reference' => $appointment->reference_no,
            'appointment_date' => $appointment->scheduled_at?->format('l, d F Y') ?? '',
            'appointment_time' => $appointment->scheduled_at ? appointmentTime($appointment->scheduled_at) : '',
        ];
    }

    /** Placeholder values so a template can be previewed before anything exists. */
    public function sample(): array
    {
        return [
            'company_name' => tenant_cache('company_name', '') ?: config('app.name'),
            'customer_name' => 'Sample Customer',
            'property_name' => '1204',
            'unit_number' => '1204',
            'building_name' => 'Marina Tower',
            'project_name' => 'Bin Al Sheikh Marina Tower',
            'employee_name' => 'Sample Employee',
            'employee_phone' => '+974 0000 0000',
            'agreement_no' => '#0000',
            'appointment_link' => url('/appointment/b/sample-token'),
            'appointment_button' => EmailStyler::button(
                url('/appointment/b/sample-token'),
                'Choose your appointment time',
                EmailTemplateRenderer::accent()
            ),
            'link_expires_at' => now()->addDays(14)->format('d M Y'),
            'appointment_reference' => 'VW-'.now()->format('Y').'-0000',
            'appointment_date' => now()->addDays(3)->format('l, d F Y'),
            'appointment_time' => appointmentTime('16:00'),
        ];
    }
}
