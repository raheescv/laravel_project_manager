<?php

/*
|--------------------------------------------------------------------------
| Property Appointment Scheduler
|--------------------------------------------------------------------------
|
| Operational defaults for the appointment scheduler. These are STARTING points a
| tenant can override in the UI — nothing here is enforced at send time, and
| editing hours in Settings or on an employee never gets reset from here.
|
| The working week itself is answered in three steps, most specific first:
|   1. the employee's own weekly availability (their employee page),
|   2. the company hours in Settings -> Working Day,
|   3. the defaults below, for a tenant who has configured neither.
|
*/

return [

    /*
    | The fallback working week — used only by a tenant who has never configured
    | Settings -> Working Day. Once they have, those rows are the authority and
    | everything here is ignored, including the times: a working day whose hours
    | are left blank borrows start_time/end_time from here, one column at a time.
    |
    | slot_interval_minutes is the exception — it is the application's single
    | slot length, has no UI anywhere, and is always read from here.
    |
    | 0 = Sunday … 6 = Saturday. The default below is Sunday-Thursday, matching
    | the application's default country.
    */
    'default_availability' => [
        'days' => [0, 1, 2, 3, 4],
        'start_time' => '09:00',
        'end_time' => '18:00',
        'slot_interval_minutes' => 120,
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
