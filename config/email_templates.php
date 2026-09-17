<?php

use App\Services\PropertyAppointment\AppointmentMailData;
use App\Services\Student\ParentMailData;

/*
|--------------------------------------------------------------------------
| Email Templates
|--------------------------------------------------------------------------
|
| The registry of everything in this application that can send a templated
| email. Each module declares the events it sends and the merge variables
| those events can resolve — nothing else in the codebase decides what a
| template may reference.
|
| Adding a module here is all it takes for its templates to appear in
| Settings -> Email Templates.
|
| Each module may set a `footer_note`: the line under the company details in
| the email footer that tells the recipient why they got it.
|
| Each type may carry a `default` subject/body. That is STARTER wording only:
| it is never sent on its own and never overrides anything. A tenant explicitly
| creates a template from it in Settings and owns the copy from that moment on,
| so their edits can never be silently replaced by a code change. Sending still
| requires an active template — there is no invisible fallback.
|
*/

$common = [
    'company_name',
];

$appointmentBase = array_merge($common, [
    'customer_name',
    'property_name',
    'unit_number',
    'building_name',
    'project_name',
    'employee_name',
    'employee_phone',
    'agreement_no',
    'appointment_link',
    'appointment_button',
    'link_expires_at',
]);

$appointmentScheduled = array_merge($appointmentBase, [
    'appointment_date',
    'appointment_time',
    'appointment_reference',
]);

return [

    'student_portal' => [
        'label' => 'Parent Portal',
        // Listed only for tenants running the School module (App\Support\ModuleAccess).
        'requires_module' => 'school',
        'icon' => 'fa-graduation-cap',
        'footer_note' => 'You received this because the school has you on record as a parent.',
        'sample' => ParentMailData::class,
        // Unlike other modules, these starters are created the first time a link
        // is sent (see SendInviteAction): a parent cannot sign in without the
        // email, so a school that never opened Settings must still get one out.
        // Once created the tenant owns the wording like any other template.
        'types' => [
            'parent_invite' => [
                'label' => 'Parent Portal Invite',
                'description' => 'Sent when staff invite a parent who has not set a password yet. The subject is also the WhatsApp message.',
                'default' => [
                    'subject' => 'Your {{ company_name }} parent portal login',
                    'body' => '<p>Hello {{ parent_name }},</p>'.
                    '<p>You can now see {{ student_names }}\'s canteen card balance and bills, and top up the card online.</p>'.
                    '{{ set_password_button }}'.
                    '<p>Sign in with your mobile number {{ parent_mobile }}. This link works until {{ link_expires_at }}.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => array_merge($common, ['parent_name', 'parent_mobile', 'student_names', 'set_password_link', 'set_password_button', 'link_expires_at']),
            ],
            'parent_password_reset' => [
                'label' => 'Parent Password Reset',
                'description' => 'Sent when staff send a password reset to a parent who already signed in, or the parent uses Forgot password. The subject is also the WhatsApp message.',
                'default' => [
                    'subject' => 'Reset your {{ company_name }} parent portal password',
                    'body' => '<p>Hello {{ parent_name }},</p>'.
                    '<p>A link to choose a new password for your parent portal login has been requested.</p>'.
                    '{{ set_password_button }}'.
                    '<p>Sign in with your mobile number {{ parent_mobile }}. This link works once, until {{ link_expires_at }}.</p>'.
                    '<p>If you did not ask for this, you can ignore this email. Your current password still works.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => array_merge($common, ['parent_name', 'parent_mobile', 'student_names', 'set_password_link', 'set_password_button', 'link_expires_at']),
            ],
        ],
    ],

    'property_appointment' => [
        'label' => 'Property Appointment',
        'icon' => 'fa-calendar-check-o',
        'footer_note' => 'You received this because you enquired about a property with us.',
        // Supplies realistic values for the Settings live preview (->sample()).
        'sample' => AppointmentMailData::class,
        'types' => [
            'appointment_invite' => [
                'label' => 'Appointment Invitation',
                'description' => 'Sent when staff share the appointment link from a lease/sale agreement.',
                'default' => [
                    'subject' => 'Book your appointment for Unit {{ unit_number }}, {{ building_name }}',
                    'body' => '<p>Hello {{ customer_name }},</p>'.
                    '<p>Thank you for your interest in <strong>Unit {{ unit_number }}, {{ building_name }}</strong>. You can choose a appointment time that suits you.</p>'.
                    '<p>Your appointment will be handled by {{ employee_name }}.</p>'.
                    '{{ appointment_button }}'.
                    '<p>Please pick a time before {{ link_expires_at }}. If none of the available times work for you, just reply to this email.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => $appointmentBase,
            ],
            'appointment_confirmed' => [
                'label' => 'Appointment Confirmed',
                'description' => 'Sent to the customer once they pick a slot.',
                'default' => [
                    'subject' => 'Your appointment is confirmed for {{ appointment_date }}',
                    'body' => '<p>Hello {{ customer_name }},</p>'.
                    '<p>Your appointment for <strong>Unit {{ unit_number }}, {{ building_name }}</strong> is confirmed.</p>'.
                    '<p><strong>{{ appointment_date }} at {{ appointment_time }}</strong><br>Reference: {{ appointment_reference }}</p>'.
                    '<p>{{ employee_name }} will meet you there and can be reached on {{ employee_phone }}.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => $appointmentScheduled,
            ],
            'appointment_rescheduled' => [
                'label' => 'Appointment Rescheduled',
                'description' => 'Sent when a booked appointment moves to a different time.',
                'default' => [
                    'subject' => 'Your appointment has moved to {{ appointment_date }}',
                    'body' => '<p>Hello {{ customer_name }},</p>'.
                    '<p>Your appointment for <strong>Unit {{ unit_number }}, {{ building_name }}</strong> has been moved.</p>'.
                    '<p><strong>New time: {{ appointment_date }} at {{ appointment_time }}</strong><br>Reference: {{ appointment_reference }}</p>'.
                    '<p>If this no longer suits you, please reply to this email or call {{ employee_name }} on {{ employee_phone }}.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => $appointmentScheduled,
            ],
            'appointment_cancelled' => [
                'label' => 'Appointment Cancelled',
                'description' => 'Sent when a appointment is called off.',
                'default' => [
                    'subject' => 'Your appointment on {{ appointment_date }} has been cancelled',
                    'body' => '<p>Hello {{ customer_name }},</p>'.
                    '<p>Your appointment for <strong>Unit {{ unit_number }}, {{ building_name }}</strong> on {{ appointment_date }} at {{ appointment_time }} has been cancelled.</p>'.
                    '<p>Reference: {{ appointment_reference }}</p>'.
                    '<p>To arrange another time, reply to this email or contact {{ employee_name }} on {{ employee_phone }}.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => $appointmentScheduled,
            ],
            'appointment_reminder' => [
                'label' => 'Appointment Reminder',
                'description' => 'Sent ahead of the appointment as a reminder.',
                'default' => [
                    'subject' => 'Reminder: your appointment is on {{ appointment_date }}',
                    'body' => '<p>Hello {{ customer_name }},</p>'.
                    '<p>This is a reminder of your appointment for <strong>Unit {{ unit_number }}, {{ building_name }}</strong>.</p>'.
                    '<p><strong>{{ appointment_date }} at {{ appointment_time }}</strong><br>Reference: {{ appointment_reference }}</p>'.
                    '<p>{{ employee_name }} will meet you there on {{ employee_phone }}.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => $appointmentScheduled,
            ],
        ],
    ],

];
