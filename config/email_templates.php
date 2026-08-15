<?php

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
    'salesman_name',
    'salesman_phone',
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

    'property_appointment' => [
        'label' => 'Property Appointment',
        'icon' => 'fa-calendar-check-o',
        'types' => [
            'appointment_invite' => [
                'label' => 'Appointment Invitation',
                'description' => 'Sent when staff share the appointment link from a lease/sale agreement.',
                'default' => [
                    'subject' => 'Book your appointment for Unit {{ unit_number }}, {{ building_name }}',
                    'body' => '<p>Hello {{ customer_name }},</p>'.
                    '<p>Thank you for your interest in <strong>Unit {{ unit_number }}, {{ building_name }}</strong>. You can choose a appointment time that suits you.</p>'.
                    '<p>Your appointment will be handled by {{ salesman_name }}.</p>'.
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
                    '<p>{{ salesman_name }} will meet you there and can be reached on {{ salesman_phone }}.</p>'.
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
                    '<p>If this no longer suits you, please reply to this email or call {{ salesman_name }} on {{ salesman_phone }}.</p>'.
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
                    '<p>To arrange another time, reply to this email or contact {{ salesman_name }} on {{ salesman_phone }}.</p>'.
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
                    '<p>{{ salesman_name }} will meet you there on {{ salesman_phone }}.</p>'.
                    '<p>Kind regards,<br>{{ company_name }}</p>',
                ],
                'variables' => $appointmentScheduled,
            ],
        ],
    ],

];
