<?php

namespace App\Http\Controllers\Property;

use App\Actions\PropertyAppointment\BookAction;
use App\Enums\RentOut\AgreementType;
use App\Http\Controllers\Controller;
use App\Models\PropertyAppointment;
use App\Models\PropertyAppointmentAvailability;
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

    /** All-salesmen calendar. */
    public function calendar()
    {
        return view('property.appointment.calendar', [
            'salesmen' => User::employee()
                ->select(['id', 'name'])
                ->whereIn('id', PropertyAppointment::query()->distinct()->pluck('salesman_id')->filter())
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
                'salesman:id,name,mobile',
                'rentOut:id,property_id,agreement_type',
                'rentOut.property:id,number,property_building_id',
                'rentOut.property.building:id,name',
            ])
            ->whereNotNull('scheduled_at')
            ->when($request->salesman_id, fn ($query, $value) => $query->whereIn('salesman_id', (array) $value))
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->when($request->start, fn ($query, $value) => $query->where('scheduled_at', '>=', Carbon::parse($value)))
            ->when($request->end, fn ($query, $value) => $query->where('scheduled_at', '<=', Carbon::parse($value)))
            ->when(session('branch_id'), fn ($query, $value) => $query->where('branch_id', $value))
            ->get();

        $durations = $this->salesmanSlotMinutes($appointments->pluck('salesman_id')->filter()->unique()->all());

        return response()->json($appointments->map(function (PropertyAppointment $appointment) use ($durations) {
            $minutes = $durations[$appointment->salesman_id] ?? 60;
            $property = $appointment->rentOut?->property;
            $end = $appointment->scheduled_at?->copy()->addMinutes($minutes);
            $agreement = $this->agreementLink($appointment);

            return [
                'id' => $appointment->id,
                'title' => trim(($appointment->customer?->name ?? 'Appointment').' — '.($property?->number ?? '')),
                'start' => $appointment->scheduled_at?->toIso8601String(),
                'end' => $end?->toIso8601String(),
                'classNames' => ['pv-event-'.str_replace('_', '-', $appointment->status)],
                'extendedProps' => [
                    'reference_no' => $appointment->reference_no,
                    'status' => $appointment->status,
                    'status_label' => $appointment->statusLabel(),
                    'customer' => $appointment->customer?->name,
                    'customer_phone' => $appointment->customer?->mobile,
                    'salesman' => $appointment->salesman?->name,
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
        }));
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
     * Minutes one appointment occupies, per salesman.
     *
     * Length is a per-rule setting, so a salesman whose day mixes 30- and
     * 60-minute rules gets the SHORTEST — a block that is too short leaves a
     * gap, while one that is too long would overlap the following appointment
     * and make the grid lie about availability.
     *
     * @param  array<int, int>  $salesmanIds
     * @return array<int, int>
     */
    private function salesmanSlotMinutes(array $salesmanIds): array
    {
        if (! $salesmanIds) {
            return [];
        }

        return PropertyAppointmentAvailability::query()
            ->whereIn('user_id', $salesmanIds)
            ->where('is_active', true)
            ->get(['user_id', 'slot_interval_minutes'])
            ->groupBy('user_id')
            ->map(fn ($rules) => max(5, (int) $rules->min('slot_interval_minutes')))
            ->all();
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
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $response = (new BookAction())->execute(
            $appointment->id,
            $validated['slot'],
            'customer',
            null,
            $validated['timezone'] ?? null
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
            'salesman:id,name,mobile',
            'rentOut:id,property_id',
            'rentOut.property:id,number,property_building_id,property_type_id,rooms,size',
            'rentOut.property.building:id,name,location',
            'rentOut.property.type:id,name',
        ]);

        $bookable = $appointment->isBookable();

        $slots = $bookable
            ? app(SlotService::class)->availableSlots($appointment->salesman_id, null, null, $appointment->id)
            : [];

        $duration = $this->appointmentDuration($appointment->salesman_id);
        $property = $appointment->rentOut?->property;

        return [
            'status' => $appointment->status,
            'usable' => $appointment->isLinkUsable(),
            'reference_no' => $appointment->reference_no,
            'customer_name' => $appointment->customer?->name,
            'customer_email' => $appointment->customer?->email,
            'salesman_name' => $appointment->salesman?->name,
            'salesman_phone' => $appointment->salesman?->mobile,
            'property_name' => $property?->number,
            'property' => $property ? [
                'unit' => $property->number,
                'building' => $property->building?->name,
                'location' => $property->building?->location,
                'type' => $property->type?->name,
                'rooms' => $property->rooms,
                'size' => $property->size ? rtrim(rtrim(number_format((float) $property->size, 2, '.', ','), '0'), '.') : null,
            ] : null,
            // Null whenever the salesman's rules disagree on slot length — the
            // page then simply says nothing about duration rather than guessing.
            'duration_minutes' => $duration,
            'timezone' => config('app.timezone'),
            'expires_at' => $appointment->token_expires_at?->format('d M Y'),
            'scheduled' => $appointment->scheduled_at ? [
                'day_name' => $appointment->scheduled_at->format('l'),
                'long_date' => $appointment->scheduled_at->format('l, d F Y'),
                'short_date' => $appointment->scheduled_at->format('D d M'),
                // Year-less, for the headline — the panel underneath carries
                // the full date, so repeating the year there reads as filler.
                'headline_date' => $appointment->scheduled_at->format('l, d F'),
                'time' => appointmentTime($appointment->scheduled_at),
                'end_time' => $duration ? appointmentTime($appointment->scheduled_at->copy()->addMinutes($duration)) : null,
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

    /**
     * How long one appointment runs, in minutes.
     *
     * Slot length is a per-rule setting, so a salesman with 30-minute mornings
     * and 60-minute afternoons has no single answer — in that case this returns
     * null and the page omits the claim entirely rather than printing a wrong
     * finish time next to every slot.
     */
    private function appointmentDuration(?int $salesmanId): ?int
    {
        if (! $salesmanId) {
            return null;
        }

        $intervals = PropertyAppointmentAvailability::query()
            ->where('user_id', $salesmanId)
            ->where('is_active', true)
            ->pluck('slot_interval_minutes')
            ->unique();

        return $intervals->count() === 1 ? (int) $intervals->first() : null;
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
