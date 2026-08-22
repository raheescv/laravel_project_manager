<?php

namespace App\Http\Controllers\Property;

use App\Actions\PropertyAppointment\BookAction;
use App\Enums\RentOut\AgreementType;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\PropertyAppointment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PropertyAppointment\SlotService;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PropertyAppointmentController extends Controller
{
    /** Appointments list, sibling to the RentOut Sales appointment list. */
    public function index()
    {
        return view('property.appointment.index');
    }

    /** All-employees calendar. */
    public function calendar()
    {
        return view('property.appointment.calendar', [
            'employees' => User::employee()
                ->select(['id', 'name'])
                ->whereIn('id', PropertyAppointment::query()->distinct()->pluck('employee_id')->filter())
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * FullCalendar feed for the calendar page.
     *
     * The console shows every appointment's detail in a popover without a second
     * request, so everything that popover needs travels with the event — including
     * the link through to the agreement, which is resolved (and permission-checked)
     * here rather than guessed in JavaScript.
     */
    public function calendarData(Request $request)
    {
        $appointments = PropertyAppointment::query()
            ->with([
                'customer:id,name,mobile',
                'employee:id,name,mobile',
                'rentOut:id,property_id,agreement_type',
                'rentOut.property:id,number,property_building_id',
                'rentOut.property.building:id,name',
            ])
            ->whereNotNull('scheduled_at')
            ->when($request->employee_id, fn ($query, $value) => $query->whereIn('employee_id', (array) $value))
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->when($request->start, fn ($query, $value) => $query->where('scheduled_at', '>=', Carbon::parse($value)))
            ->when($request->end, fn ($query, $value) => $query->where('scheduled_at', '<=', Carbon::parse($value)))
            ->when(session('branch_id'), fn ($query, $value) => $query->where('branch_id', $value))
            ->get();

        $events = $appointments->map(function (PropertyAppointment $appointment) {
            $property = $appointment->rentOut?->property;
            // The customer chose how long they need, so the block is as long as
            // they asked for; older bookings fall back to the slot length.
            $end = $appointment->endsAt();
            $agreement = $this->agreementLink($appointment);

            return [
                'id' => $appointment->id,
                'title' => trim(($appointment->customer?->name ?? 'Appointment').' — '.($property?->number ?? '')),
                // A slot is a wall-clock time in the branch's day, not an
                // instant on a world clock, so the times go out WITHOUT a
                // timezone offset. FullCalendar renders in the viewer's device
                // timezone, and an offset would slide every block by the gap
                // between that device and the server.
                'start' => $appointment->scheduled_at?->format('Y-m-d\TH:i:s'),
                'end' => $end?->format('Y-m-d\TH:i:s'),
                'classNames' => ['pv-event-'.str_replace('_', '-', $appointment->status)],
                'extendedProps' => [
                    'reference_no' => $appointment->reference_no,
                    'status' => $appointment->status,
                    'status_label' => $appointment->statusLabel(),
                    'customer' => $appointment->customer?->name,
                    'customer_phone' => $appointment->customer?->mobile,
                    'employee' => $appointment->employee?->name,
                    'property' => $property?->number,
                    'building' => $property?->building?->name,
                    'long_date' => $appointment->scheduled_at?->format('l, d F Y'),
                    'time_range' => trim(appointmentTime($appointment->scheduled_at).($end ? ' – '.appointmentTime($end) : '')),
                    'booked' => $this->bookedLabel($appointment),
                    'agreement_url' => $agreement['url'],
                    'agreement_label' => $agreement['label'],
                    'booking_url' => $appointment->token && $appointment->isLinkUsable()
                        ? route('property_appointment::public', $appointment->token)
                        : null,
                ],
            ];
        })->values();

        // Company holidays travel with the appointments so the console can shade
        // a closed day without a second request. They are background events, not
        // blocks: nothing is booked on them, they just explain the empty column.
        // The employee and status filters deliberately do NOT apply — a closure
        // is the whole company's, so it stays on screen whoever is being viewed.
        return response()->json($events->concat($this->holidayEvents($request))->all());
    }

    /**
     * The holiday shading for a calendar range, as FullCalendar background events.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function holidayEvents(Request $request)
    {
        $from = $request->start ? Carbon::parse($request->start) : now()->startOfMonth();
        $to = $request->end ? Carbon::parse($request->end) : now()->endOfMonth();

        return collect(Holiday::datesBetween($from, $to))->map(fn (string $name, string $date) => [
            'id' => 'holiday-'.$date,
            'title' => $name,
            'start' => $date,
            // FullCalendar's end is exclusive, so a one-day holiday runs to the
            // next morning or it paints nothing at all.
            'end' => Carbon::parse($date)->addDay()->toDateString(),
            'allDay' => true,
            'display' => 'background',
            'classNames' => ['pv-holiday'],
            'extendedProps' => ['holiday' => true],
        ])->values();
    }

    /**
     * Where the popover's primary action goes.
     *
     * Lease and rental agreements live on different screens behind different
     * permissions, so the URL is only handed out when this user may actually
     * open it — a link the viewer would only get a 403 from is worse than none.
     *
     * @return array{url: ?string, label: ?string}
     */
    private function agreementLink(PropertyAppointment $appointment): array
    {
        $rentOut = $appointment->rentOut;

        if (! $rentOut) {
            return ['url' => null, 'label' => null];
        }

        $isLease = $rentOut->agreement_type === AgreementType::Lease;
        $permission = $isLease ? 'rent out lease.view' : 'rent out.view';

        if (! auth()->user()?->can($permission)) {
            return ['url' => null, 'label' => null];
        }

        return [
            'url' => route($isLease ? 'property::sale::view' : 'property::rent::view', $rentOut->id),
            'label' => $isLease ? 'Open sale / lease view' : 'Open rent-out view',
        ];
    }

    private function bookedLabel(PropertyAppointment $appointment): ?string
    {
        if (! $appointment->booked_at) {
            return null;
        }

        $who = $appointment->booked_by === 'customer' ? 'by customer' : 'by staff';

        return $who.' · '.$appointment->booked_at->format('d M');
    }

    /**
     * The customer-facing appointment page.
     *
     * The page itself is a thin shell — a Vue app fetches its data from
     * publicData() and books through publicBook(), so choosing a day or a slot
     * costs nothing over the network. Only the actual appointment hits the server.
     */
    public function publicPage(string $token)
    {
        $appointment = $this->resolveByToken($token);

        // Record that the customer opened the link, so staff can tell the
        // difference between "never saw it" and "saw it and did not book".
        $appointment->forceFill([
            'link_opened_at' => now(),
            'link_opened_count' => $appointment->link_opened_count + 1,
        ])->saveQuietly();

        return view('property.appointment.public', ['token' => $token]);
    }

    /** Everything the appointment page needs, in one payload. */
    public function publicData(string $token)
    {
        $appointment = $this->resolveByToken($token);

        return response()->json($this->payload($appointment));
    }

    /** Commit the customer's slot choice. */
    public function publicBook(Request $request, string $token)
    {
        $appointment = $this->resolveByToken($token);

        $validated = $request->validate([
            'slot' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:slot'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $response = (new BookAction())->execute(
            $appointment->id,
            $validated['slot'],
            'customer',
            null,
            $validated['timezone'] ?? null,
            $validated['ends_at'] ?? null
        );

        // Always hand back fresh slots so a losing race can re-render without
        // the client having to make a second request.
        $payload = $this->payload($appointment->fresh());
        $payload['success'] = $response['success'];
        $payload['message'] = $response['message'];
        $payload['slot_taken'] = (bool) ($response['slot_taken'] ?? false);

        return response()->json($payload, $response['success'] ? 200 : 409);
    }

    /**
     * Resolve a appointment FROM ITS TOKEN and pin the tenant.
     *
     * A guest request has no session and may arrive on a domain that
     * IdentifyTenant cannot parse a subdomain from — in which case TenantScope
     * would apply no filter at all and the request could read across every
     * tenant. Looking the token up outside the scope (it is a globally unique
     * UUID) and then pinning the tenant makes that structurally impossible.
     */
    private function resolveByToken(string $token): PropertyAppointment
    {
        $appointment = PropertyAppointment::withoutTenant()->where('token', $token)->first();

        abort_if(! $appointment, 404);

        $tenant = Tenant::find($appointment->tenant_id);
        abort_if(! $tenant, 404);

        app(TenantService::class)->setCurrentTenant($tenant);

        return $appointment;
    }

    /** @return array<string, mixed> */
    private function payload(PropertyAppointment $appointment): array
    {
        $appointment->load([
            'customer:id,name,email,mobile',
            'employee:id,name,mobile',
            'rentOut:id,property_id',
            'rentOut.property:id,number,property_building_id,property_type_id,rooms,size',
            'rentOut.property.building:id,name,location',
            'rentOut.property.type:id,name',
        ]);

        $bookable = $appointment->isBookable();
        $slotService = app(SlotService::class);

        // The page offers two ways to the same window — tap a suggested time, or
        // type your own — so it needs both the grid AND the edges the grid was
        // cut from, plus what is already taken out of each day.
        $slots = $bookable ? $slotService->availableSlots($appointment->employee_id, null, null, $appointment->id) : [];
        $windows = $bookable ? $slotService->openWindows($appointment->employee_id) : [];
        $busy = $bookable ? $slotService->busyStretches($appointment->employee_id, null, null, $appointment->id) : [];

        $duration = SlotService::slotLengthMinutes();
        $property = $appointment->rentOut?->property;

        return [
            'status' => $appointment->status,
            'usable' => $appointment->isLinkUsable(),
            'reference_no' => $appointment->reference_no,
            'customer_name' => $appointment->customer?->name,
            'customer_email' => $appointment->customer?->email,
            'employee_name' => $appointment->employee?->name,
            'employee_phone' => $appointment->employee?->mobile,
            'property_name' => $property?->number,
            'property' => $property ? [
                'unit' => $property->number,
                'building' => $property->building?->name,
                'location' => $property->building?->location,
                'type' => $property->type?->name,
                'rooms' => $property->rooms,
                'size' => $property->size ? rtrim(rtrim(number_format((float) $property->size, 2, '.', ','), '0'), '.') : null,
            ] : null,
            'duration_minutes' => $duration,
            'timezone' => config('app.timezone'),
            // Everything the typed fields validate against, so a customer is
            // told what is wrong before they submit rather than after.
            'windows' => $windows,
            'busy' => $busy,
            // Closed dates, so a greyed-out day on the customer's calendar can
            // say WHY rather than looking like a bug.
            'holidays' => $bookable
                ? Holiday::datesBetween(now(), now()->addDays(SlotService::appointmentWindowDays()))
                : [],
            'notice_hours' => SlotService::minimumNoticeHours(),
            // 12 or 24, so the typed fields label times the way the tenant's
            // own format string does without shipping the format to the client.
            'clock' => str_contains((string) config('property_appointment.time_format', 'h:i A'), 'H') ? 24 : 12,
            'window_days' => SlotService::appointmentWindowDays(),
            'server_now' => now()->format('Y-m-d H:i:s'),
            'now_label' => now()->format('l, d M').' · '.appointmentTime(now()),
            'expires_at' => $appointment->token_expires_at?->format('d M Y'),
            'scheduled' => $appointment->scheduled_at ? [
                'day_name' => $appointment->scheduled_at->format('l'),
                'long_date' => $appointment->scheduled_at->format('l, d F Y'),
                'short_date' => $appointment->scheduled_at->format('D d M'),
                // Year-less, for the headline — the panel underneath carries
                // the full date, so repeating the year there reads as filler.
                'headline_date' => $appointment->scheduled_at->format('l, d F'),
                'time' => appointmentTime($appointment->scheduled_at),
                'end_time' => $appointment->endsAt() ? appointmentTime($appointment->endsAt()) : null,
                'minutes' => $appointment->endsAt()
                    ? (int) $appointment->scheduled_at->diffInMinutes($appointment->endsAt())
                    : $duration,
            ] : null,
            'days' => collect($slots)->map(fn ($daySlots, $day) => [
                'date' => $day,
                'weekday' => Carbon::parse($day)->format('D'),
                'day' => Carbon::parse($day)->format('d'),
                'month' => Carbon::parse($day)->format('M'),
                'long_label' => Carbon::parse($day)->format('l, d F'),
                'slots' => collect($daySlots)->map(fn (array $slot) => $slot + [
                    'end_label' => $duration ? appointmentTime(Carbon::parse($slot['value'])->addMinutes($duration)) : null,
                    'part' => $this->partOfDay(Carbon::parse($slot['value'])),
                ])->all(),
            ])->values()->all(),
        ];
    }

    private function partOfDay(Carbon $slot): string
    {
        return match (true) {
            $slot->hour < 12 => 'Morning',
            $slot->hour < 17 => 'Afternoon',
            default => 'Evening',
        };
    }
}
