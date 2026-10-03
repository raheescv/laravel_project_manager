<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\PropertyAppointment\BookAction;
use App\Actions\PropertyAppointment\CancelAction;
use App\Actions\PropertyAppointment\CreateAction;
use App\Actions\PropertyAppointment\RevokeLinkAction;
use App\Actions\PropertyAppointment\SendLinkAction;
use App\Actions\PropertyAppointment\StatusAction;
use App\Actions\PropertyAppointment\UpdateAction;
use App\Models\PropertyAppointment;
use App\Models\RentOut;
use App\Models\User;
use App\Services\PropertyAppointment\SlotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AppointmentTab extends Component
{
    public $rentOutId;

    public $linkValidUntil;

    /** First day the customer may book (Y-m-d). */
    public $availableFrom;

    /** Last day the customer may book (Y-m-d). */
    public $availableUntil;

    /**
     * Who carries out the appointment.
     *
     * A fresh choice, deliberately NOT seeded from the agreement's salesman:
     * the person who shows a property is routinely not the person who owns the
     * lease, so the tab asks rather than assumes.
     */
    public $employee_id = '';

    /** Staff-side "book on the customer's behalf" state. */
    public $showSlotPicker = false;

    /** Before a link exists the card has two jobs: 'send' a link, or 'book' for the customer. */
    public $mode = 'send';

    /** The month the calendar is showing (Y-m). */
    public $calendarMonth;

    /** First day clicked when choosing the range on the calendar; the next click closes it. */
    public $rangeAnchor;

    /** How far ahead the range calendar may page. */
    public const RANGE_CALENDAR_MONTHS = 12;

    public $selectedDate;

    public $selectedSlot;

    protected $listeners = [
        'PropertyAppointment-Refresh-Component' => '$refresh',
    ];

    /** Seconds between background checks for a appointment made by the customer. */
    public const POLL_SECONDS = 20;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->linkValidUntil = now()->addDays(14)->format('Y-m-d');
        $this->employee_id = $this->appointment?->employee_id ?? '';
        $this->seedBookingRange();
        $this->calendarMonth = substr($this->availableFrom, 0, 7);
    }

    /** The range on the appointment when there is one, otherwise today + the rolling window. */
    private function seedBookingRange(): void
    {
        $appointment = $this->appointment;

        $this->availableFrom = $appointment?->available_from?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->availableUntil = $appointment?->available_until?->format('Y-m-d')
            ?? now()->addDays(SlotService::appointmentWindowDays())->format('Y-m-d');
    }

    public function getRentOutProperty(): ?RentOut
    {
        return RentOut::with(['account:id,name,email,mobile', 'property:id,number'])
            ->find($this->rentOutId);
    }

    public function getAppointmentProperty(): ?PropertyAppointment
    {
        return PropertyAppointment::with(['employee:id,name,mobile', 'customer:id,name,email,mobile', 'emailLogs'])
            ->where('rent_out_id', $this->rentOutId)
            ->whereNot('status', 'cancelled')
            ->latest('id')
            ->first();
    }

    /** The chosen employee, for the panel heading and the slot picker's copy. */
    public function getEmployeeProperty(): ?User
    {
        return $this->employee_id
            ? User::select(['id', 'name', 'mobile'])->find($this->employee_id)
            : null;
    }

    /** Slot grid for the staff-side picker, grouped by day. */
    public function getSlotsProperty(): array
    {
        if (! $this->employee_id) {
            return [];
        }

        $range = $this->rangeDates();
        if (! $range) {
            return [];
        }

        return app(SlotService::class)->availableSlots(
            (int) $this->employee_id,
            Carbon::parse($range[0])->startOfDay(),
            Carbon::parse($range[1])->endOfDay(),
            $this->appointment?->id
        );
    }

    /**
     * The range as clamped Y-m-d strings, or null while it is unusable.
     *
     * @return array{0: string, 1: string}|null
     */
    private function rangeDates(): ?array
    {
        if (blank($this->availableFrom) || blank($this->availableUntil) || $this->availableUntil < $this->availableFrom) {
            return null;
        }

        [$from, $to] = SlotService::bookingRange(Carbon::parse($this->availableFrom), Carbon::parse($this->availableUntil));

        return [$from->toDateString(), $to->toDateString()];
    }

    /**
     * Open days, free times and closed days inside the range.
     *
     * @return array{open: int, free: int, closed: int, days: int}
     */
    public function getRangeStatsProperty(): array
    {
        $range = $this->rangeDates();
        if (! $range) {
            return ['open' => 0, 'free' => 0, 'closed' => 0, 'days' => 0];
        }

        $slots = $this->slots;
        $days = (int) Carbon::parse($range[0])->diffInDays(Carbon::parse($range[1])) + 1;

        return [
            'open' => count($slots),
            'free' => collect($slots)->sum(fn (array $daySlots) => count($daySlots)),
            'closed' => $days - count($slots),
            'days' => $days,
        ];
    }

    /**
     * The month grid: range shading, free-time counts and the arrow limits.
     *
     * @return array{label: string, blanks: int, canBack: bool, canForward: bool, canBackAny: bool, canForwardAny: bool, cells: array<int, array<string, mixed>>}
     */
    public function getCalendarProperty(): array
    {
        $range = $this->rangeDates();
        $month = Carbon::parse(($this->calendarMonth ?: now()->format('Y-m')).'-01');
        $slots = $this->slots;
        $inRange = fn (string $date) => $range && $date >= $range[0] && $date <= $range[1];

        $cells = [];
        foreach (range(1, $month->daysInMonth) as $day) {
            $date = $month->copy()->day($day);
            $key = $date->toDateString();
            $in = $inRange($key);

            $cells[] = [
                'date' => $key,
                'day' => $day,
                'in' => $in,
                'free' => count($slots[$key] ?? []),
                'first' => $in && (! $inRange($date->copy()->subDay()->toDateString()) || $date->dayOfWeek === 0),
                'last' => $in && (! $inRange($date->copy()->addDay()->toDateString()) || $date->dayOfWeek === 6 || $day === $month->daysInMonth),
                'today' => $date->isToday(),
                'past' => $date->lt(today()),
            ];
        }

        return [
            'label' => $month->format('F Y'),
            'blanks' => $month->dayOfWeek,
            'canBack' => $range && $month->format('Y-m') > substr($range[0], 0, 7),
            'canForward' => $range && $month->format('Y-m') < substr($range[1], 0, 7),
            'canBackAny' => $month->format('Y-m') > now()->format('Y-m'),
            'canForwardAny' => $month->format('Y-m') < now()->addMonthsNoOverflow(self::RANGE_CALENDAR_MONTHS)->format('Y-m'),
            'cells' => $cells,
        ];
    }

    /**
     * Choosing the range on the calendar: the first click marks one end, the
     * second closes it, in whichever order the two days were clicked.
     */
    public function pickRangeDay(string $date): void
    {
        if ($date < now()->format('Y-m-d')) {
            return;
        }

        if (blank($this->rangeAnchor)) {
            $this->rangeAnchor = $date;

            return;
        }

        $this->availableFrom = min($this->rangeAnchor, $date);
        $this->availableUntil = max($this->rangeAnchor, $date);
        $this->bookingRangeChanged(false);
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'book' ? 'book' : 'send';
        $this->reset(['selectedSlot', 'rangeAnchor']);
    }

    public function shiftMonth(int $step): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')->addMonthsNoOverflow($step)->format('Y-m');
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedSlot = null;
    }

    /** Quick ranges: today plus a number of days. */
    public function applyPreset(int $days): void
    {
        $this->availableFrom = now()->format('Y-m-d');
        $this->availableUntil = now()->addDays($days)->format('Y-m-d');
        $this->bookingRangeChanged();
    }

    public function updatedAvailableFrom(): void
    {
        $this->bookingRangeChanged();
    }

    public function updatedAvailableUntil(): void
    {
        $this->bookingRangeChanged();
    }

    /**
     * A new range redraws the grid, and on an existing appointment it is saved
     * straight away — the customer's link reads the range from the record.
     */
    private function bookingRangeChanged(bool $jumpToStart = true): void
    {
        $this->reset(['selectedSlot', 'selectedDate', 'rangeAnchor']);
        unset($this->slots);
        if ($jumpToStart) {
            $this->calendarMonth = substr($this->rangeDates()[0] ?? now()->format('Y-m-d'), 0, 7);
        }

        $appointment = $this->appointment;
        if (! $appointment) {
            return;
        }

        abort_unless(auth()->user()?->can('property appointment.edit'), 403);
        $this->runAction(fn () => (new UpdateAction())->execute([
            'available_from' => $this->availableFrom ?: null,
            'available_until' => $this->availableUntil ?: null,
        ], $appointment->id, Auth::id()));

        $this->seedBookingRange();
    }

    /**
     * Picking someone else.
     *
     * Before a appointment exists this is just held state. Once one exists the
     * change is written straight through, because the slots on screen, the link
     * the customer holds and the diary the booking sits in all belong to that
     * person — leaving the three disagreeing until some later Save is exactly
     * how a customer ends up meeting nobody.
     */
    public function updatedEmployeeId($value)
    {
        // A slot chosen from the previous person's diary means nothing now.
        $this->reset(['selectedSlot', 'selectedDate']);
        unset($this->slots, $this->employee);

        $appointment = $this->appointment;

        if (! $appointment || (int) $appointment->employee_id === (int) $value) {
            $this->syncEmployeePicker();

            return;
        }

        if (blank($value)) {
            $this->dispatch('error', ['message' => 'An appointment always needs an employee. Pick a different one instead.']);
        } else {
            abort_unless(auth()->user()?->can('property appointment.edit'), 403);
            $this->runAction(fn () => (new UpdateAction())->execute(['employee_id' => $value], $appointment->id, Auth::id()));
        }

        // Whatever the database settled on wins — a rejected change must not
        // leave the picker showing someone who is not on the appointment.
        $this->employee_id = $this->appointment?->employee_id ?? '';
        unset($this->slots, $this->employee);
        $this->syncEmployeePicker();
    }

    /** Push the authoritative choice back to the TomSelect behind wire:ignore. */
    private function syncEmployeePicker(): void
    {
        $this->dispatch('appointment-employee-synced', [
            'id' => (string) $this->employee_id,
            'name' => $this->employee?->name ?? '',
        ]);
    }

    public function sendLink()
    {
        abort_unless(auth()->user()?->can('property appointment.send link'), 403);
        try {
            DB::beginTransaction();

            $appointment = $this->appointment;
            if (! $appointment) {
                $response = (new CreateAction())->execute([
                    'rent_out_id' => $this->rentOutId,
                    'employee_id' => $this->employee_id,
                    'token_expires_at' => $this->linkValidUntil,
                    'available_from' => $this->availableFrom ?: null,
                    'available_until' => $this->availableUntil ?: null,
                ], Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
                $appointment = $response['data'];
            }

            $type = $appointment->status === 'scheduled' ? 'appointment_confirmed' : 'appointment_invite';
            $response = (new SendLinkAction())->execute($appointment->id, $type, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            DB::commit();
            $this->freshen();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function revokeLink()
    {
        abort_unless(auth()->user()?->can('property appointment.send link'), 403);
        $this->runAction(fn () => (new RevokeLinkAction())->execute($this->appointment?->id, Auth::id()));
    }

    public function bookSlot()
    {
        abort_unless(auth()->user()?->can('property appointment.create'), 403);

        if (blank($this->selectedSlot)) {
            $this->dispatch('error', ['message' => 'Please choose a time slot first.']);

            return;
        }

        try {
            $appointment = $this->appointment;
            if (! $appointment) {
                DB::beginTransaction();
                $response = (new CreateAction())->execute([
                    'rent_out_id' => $this->rentOutId,
                    'employee_id' => $this->employee_id,
                    'token_expires_at' => $this->linkValidUntil,
                    'available_from' => $this->availableFrom ?: null,
                    'available_until' => $this->availableUntil ?: null,
                ], Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
                DB::commit();
                $appointment = $response['data'];
            }

            // BookAction owns its own transaction because the same code path
            // serves the public endpoint — do not wrap it here.
            $response = (new BookAction())->execute($appointment->id, $this->selectedSlot, 'staff', Auth::id());

            if (! $response['success']) {
                $this->dispatch('error', ['message' => $response['message']]);

                return;
            }

            $this->reset(['showSlotPicker', 'selectedSlot', 'selectedDate', 'mode']);
            $this->freshen();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function cancel()
    {
        abort_unless(auth()->user()?->can('property appointment.edit'), 403);
        $this->runAction(fn () => (new CancelAction())->execute($this->appointment?->id, Auth::id(), 'Cancelled by staff'));
    }

    public function markStatus($status)
    {
        abort_unless(auth()->user()?->can('property appointment.edit'), 403);
        $this->runAction(fn () => (new StatusAction())->execute($this->appointment?->id, $status, Auth::id()));
    }

    private function runAction(callable $callback): void
    {
        try {
            DB::beginTransaction();
            $response = $callback();
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->freshen();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Drop the memoised reads so the panel re-renders from the database.
     *
     * Livewire caches a getXProperty() result for the whole request. An action
     * that touches $this->appointment therefore leaves a STALE model behind, and
     * render() would redraw the old status until the user reloaded the page —
     * which is exactly the "have to refresh to see it" symptom.
     */
    private function freshen(): void
    {
        unset($this->appointment, $this->rentOut, $this->slots, $this->employee);
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.appointment-tab');
    }
}
