{{-- The chosen day's free times, grouped by part of day, and the confirm bar. --}}
@php
    $daySlots = $slots[$activeDay] ?? [];
    $dayDate = $activeDay ? \Carbon\Carbon::parse($activeDay) : null;
    $slotMinutes = \App\Services\PropertyAppointment\SlotService::slotLengthMinutes();
    $parts = [
        ['Morning', 'fa-sun-o', fn (int $hour) => $hour < 12],
        ['Afternoon', 'fa-adjust', fn (int $hour) => $hour >= 12 && $hour < 17],
        ['Evening', 'fa-moon-o', fn (int $hour) => $hour >= 17],
    ];
    $chosen = $selectedSlot ? \Carbon\Carbon::parse($selectedSlot) : null;
@endphp
<div class="apx-dayc">
    <div class="apx-dayc-h">
        @if ($dayDate)
            <div class="apx-dt">
                <div class="mo">{{ $dayDate->format('D') }}</div>
                <div class="dy">{{ $dayDate->format('d') }}</div>
            </div>
            <div>
                <div class="tt">{{ $dayDate->format('l, d F') }}</div>
                <div class="ss">{{ count($daySlots) }} {{ \Illuminate\Support\Str::plural('time', count($daySlots)) }} free &middot; {{ $employee->name }}</div>
            </div>
        @else
            <div class="apx-dt wait"><i class="fa fa-calendar-o"></i></div>
            <div>
                <div class="tt">No bookable day</div>
                <div class="ss">Nothing is open in the chosen dates</div>
            </div>
        @endif
    </div>

    <div class="apx-dayc-b">
        @if (empty($daySlots))
            <div class="apx-closed-note">
                <i class="fa fa-calendar-o"></i>
                {{ $employee->name }} has no free times
                @if ($dayDate) on this day. Pick another date. @else between these dates. Widen the range, or check Settings &rarr; Working Day. @endif
            </div>
        @else
            @foreach ($parts as [$partLabel, $partIcon, $inPart])
                @php $partSlots = array_filter($daySlots, fn ($slot) => $inPart((int) substr($slot['value'], 11, 2))); @endphp
                @if ($partSlots)
                    <div class="apx-part">
                        <div class="ph"><i class="fa {{ $partIcon }}"></i> {{ $partLabel }}</div>
                        <div class="apx-slots">
                            @foreach ($partSlots as $slot)
                                <button type="button" class="apx-slot {{ $selectedSlot === $slot['value'] ? 'sel' : '' }}"
                                    wire:click="$set('selectedSlot', '{{ $slot['value'] }}')">
                                    <b>{{ $slot['label'] }}</b>
                                    <small>until {{ appointmentTime(\Carbon\Carbon::parse($slot['value'])->addMinutes($slotMinutes)) }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>

    <div class="apx-confirm">
        <div class="sum">
            @if ($chosen)
                <b>{{ $chosen->format('l, d F') }} &middot; {{ appointmentTime($chosen) }} &ndash; {{ appointmentTime($chosen->copy()->addMinutes($slotMinutes)) }}</b>
                with {{ $employee->name }} &middot; {{ config('app.timezone') }}
            @else
                Pick a time to confirm the appointment
            @endif
        </div>
        @if ($onCancel ?? null)
            <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs" wire:click="{{ $onCancel }}">Cancel</button>
        @endif
        <button type="button" class="apx-btn apx-btn-primary" wire:click="bookSlot" wire:loading.attr="disabled"
            wire:target="bookSlot" @disabled(blank($selectedSlot))>
            <i class="fa fa-check-circle"></i> Confirm appointment
        </button>
    </div>
</div>
