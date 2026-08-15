<?php

/*
|--------------------------------------------------------------------------
| Property Appointment Scheduler
|--------------------------------------------------------------------------
|
| Operational defaults for the appointment scheduler. These are STARTING points a
| tenant can override per salesman in the UI — nothing here is enforced at
| send time, and editing a salesman's hours never gets reset from here.
|
*/

return [

    /*
    | The working week applied by the "Default timing" button.
    |
    | `days` is only consulted when the tenant has NOT configured Settings ->
    | Working Day. When they have, that is the authority and this is ignored,
    | so the default week always matches the week the business actually keeps.
    |
    | 0 = Sunday … 6 = Saturday. The default below is Sunday-Thursday, matching
    | the application's default country.
    */
    'default_availability' => [
        'days' => [0, 1, 2, 3, 4],
        'start_time' => '09:00',
        'end_time' => '18:00',
        'slot_interval_minutes' => 60,
    ],

    /*
    | How times are shown to customers and staff throughout the scheduler.
    | 'h:i A' is 12-hour with AM/PM and matches the rest of the application.
    | Use 'H:i' for a 24-hour clock.
    */
    'time_format' => 'h:i A',

    /* How far ahead a customer may book. */
    'appointment_window_days' => 30,

    /* How much notice a slot needs before it can be taken. */
    'minimum_notice_hours' => 4,

    /*
    | How many hours before the appointment the reminder email is queued by
    | appointments:send-reminders. Set to 0 to switch reminders off entirely;
    | deactivating the "Appointment Reminder" template does the same per tenant.
    */
    'reminder_hours_before' => 24,

];
