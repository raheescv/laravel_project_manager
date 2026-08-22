<div class="apx p-3">
    <x-property-appointment.premium />

    <div class="row g-3">
        {{-- upcoming appointments --}}
        <div class="col-lg-7">
            <div class="apx-panel">
                <div class="apx-panel-h">
                    <span class="apx-ico"><i class="fa fa-calendar"></i></span>
                    <div class="flex-grow-1">
                        <h4>Upcoming appointments</h4>
                        <div class="sub">Confirmed appointments ahead &middot; {{ config('app.timezone') }}</div>
                    </div>
                    <span class="apx-chip chip-scheduled"><span class="dot"></span> {{ $upcoming->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="apx-tbl">
                        <thead>
                            <tr><th>When</th><th>Customer</th><th>Property</th><th>Reference</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($upcoming as $appointment)
                                <tr wire:key="up-{{ $appointment->id }}">
                                    <td>
                                        <div class="strong">{{ $appointment->scheduled_at->format('D d M') }}</div>
                                        <div class="dim">{{ appointmentTime($appointment->scheduled_at) }}</div>
                                    </td>
                                    <td class="strong">{{ $appointment->customer?->name }}</td>
                                    <td>{{ $appointment->rentOut?->property?->number ?? '—' }}</td>
                                    <td><span class="ref">{{ $appointment->reference_no }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">
                                    <div class="apx-empty">
                                        <div class="art"><i class="fa fa-calendar-o"></i></div>
                                        <h3>No upcoming appointments</h3>
                                        <p>Nothing is booked with this employee yet.</p>
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- availability + time off --}}
        <div class="col-lg-5">
            <div class="apx-panel mb-3">
                <div class="apx-panel-h flex-wrap gap-2">
                    <span class="apx-ico"><i class="fa fa-clock-o"></i></span>
                    <div class="flex-grow-1">
                        <h4>Weekly availability</h4>
                        <div class="sub">The hours customers can book into</div>
                    </div>
                    @can('property appointment.manage availability')
                        @if ($companyHours)
                            <button type="button" class="apx-btn apx-btn-soft" wire:click="addDefaultHours"
                                title="Copies the company hours from Settings → Working Day"
                                wire:confirm="Copy the company hours onto every working day that has none yet? Days you have already set are left untouched.">
                                <i class="fa fa-magic"></i> Company hours
                            </button>
                        @endif
                    @endcan
                </div>
                <div class="apx-panel-b">
                    @forelse ($days as $index => $dayName)
                        @if (isset($availabilities[$index]))
                            <div class="apx-kv">
                                <div class="k">{{ $dayName }}</div>
                                <div class="v d-flex gap-2 flex-wrap">
                                    @foreach ($availabilities[$index] as $rule)
                                        <span class="apx-chip chip-scheduled" wire:key="rule-{{ $rule->id }}">
                                            {{ \Illuminate\Support\Str::of($rule->start_time)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($rule->end_time)->substr(0, 5) }}
                                            @can('property appointment.manage availability')
                                                <a href="#" wire:click.prevent="removeAvailability({{ $rule->id }})"
                                                    style="color:inherit"><i class="fa fa-times"></i></a>
                                            @endcan
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @empty
                    @endforelse

                    {{-- No personal hours is not a dead end: the company week from
                         Settings → Working Day is what customers are offered, so the
                         panel states those hours rather than warning about none. --}}
                    @if ($availabilities->isEmpty() && $companyHours)
                        <div class="apx-alert alert-info">
                            <i class="fa fa-building-o lead"></i>
                            <div class="flex-grow-1">
                                <div class="t">Following the company hours</div>
                                <div class="s">This employee has no hours of their own, so customers are offered the
                                    company week from Settings → Working Day. Add hours below only where this employee
                                    differs.</div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    @foreach ($companyHours as $dayIndex => $timing)
                                        <span class="apx-chip chip-scheduled" wire:key="company-{{ $dayIndex }}">
                                            {{ \Illuminate\Support\Str::of($days[$dayIndex])->substr(0, 3) }}
                                            {{ $timing['start_time'] }}–{{ $timing['end_time'] }}
                                        </span>
                                    @endforeach
                                </div>
                                @can('property appointment.manage availability')
                                    <button type="button" class="apx-btn apx-btn-primary mt-2" wire:click="addDefaultHours">
                                        <i class="fa fa-magic"></i>
                                        Copy them in to edit per day
                                    </button>
                                @endcan
                            </div>
                        </div>
                    @elseif ($availabilities->isEmpty())
                        <div class="apx-alert alert-warn">
                            <i class="fa fa-exclamation-circle lead"></i>
                            <div class="flex-grow-1">
                                <div class="t">No availability set</div>
                                <div class="s">This employee has no hours of their own and every day is switched off in
                                    Settings → Working Day, so customers see no bookable times and the appointment link
                                    shows an empty calendar.</div>
                            </div>
                        </div>
                    @endif

                    @can('property appointment.manage availability')
                        <div class="row g-2 mt-3 pt-3" style="border-top:1px solid var(--border)">
                            <div class="col-5">
                                <label class="form-label" style="font-size:10.5px;font-weight:700">Day</label>
                                <select class="form-select form-select-sm" wire:model="day_of_week">
                                    @foreach ($days as $index => $dayName)
                                        <option value="{{ $index }}">{{ $dayName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label" style="font-size:10.5px;font-weight:700">From</label>
                                <input type="time" class="form-control form-control-sm" wire:model="start_time">
                            </div>
                            <div class="col-4">
                                <label class="form-label" style="font-size:10.5px;font-weight:700">To</label>
                                <input type="time" class="form-control form-control-sm" wire:model="end_time">
                            </div>
                            <div class="col-12">
                                <button type="button" class="apx-btn apx-btn-primary apx-btn-block" wire:click="addAvailability">
                                    <i class="fa fa-plus"></i> Add hours
                                </button>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="apx-panel">
                <div class="apx-panel-h">
                    <span class="apx-ico"><i class="fa fa-ban"></i></span>
                    <div class="flex-grow-1">
                        <h4>Time off</h4>
                        <div class="sub">Overrides the weekly hours</div>
                    </div>
                </div>
                <div class="apx-panel-b">
                    @forelse ($timeOffs as $off)
                        <div class="apx-kv" wire:key="off-{{ $off->id }}">
                            <div class="k">{{ $off->date->format('d M Y') }}</div>
                            <div class="v flex-grow-1">
                                {{ $off->isFullDay()
                                    ? 'Full day'
                                    : \Illuminate\Support\Str::of($off->start_time)->substr(0, 5).'–'.\Illuminate\Support\Str::of($off->end_time)->substr(0, 5) }}
                                @if ($off->reason)
                                    <span style="color:var(--text-3);font-weight:500"> &middot; {{ $off->reason }}</span>
                                @endif
                            </div>
                            @can('property appointment.manage availability')
                                <a href="#" wire:click.prevent="removeTimeOff({{ $off->id }})"
                                    style="color:var(--danger)"><i class="fa fa-times"></i></a>
                            @endcan
                        </div>
                    @empty
                        <div class="apx-hint">No time off recorded.</div>
                    @endforelse

                    @can('property appointment.manage availability')
                        <div class="row g-2 mt-3 pt-3" style="border-top:1px solid var(--border)">
                            <div class="col-6">
                                <label class="form-label" style="font-size:10.5px;font-weight:700">Date</label>
                                <input type="date" class="form-control form-control-sm" wire:model="off_date">
                            </div>
                            <div class="col-3">
                                <label class="form-label" style="font-size:10.5px;font-weight:700">From</label>
                                <input type="time" class="form-control form-control-sm" wire:model="off_start_time">
                            </div>
                            <div class="col-3">
                                <label class="form-label" style="font-size:10.5px;font-weight:700">To</label>
                                <input type="time" class="form-control form-control-sm" wire:model="off_end_time">
                            </div>
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm" wire:model="off_reason"
                                    placeholder="Reason (optional)">
                            </div>
                            <div class="col-4">
                                <button type="button" class="apx-btn apx-btn-ghost apx-btn-block" wire:click="addTimeOff">
                                    <i class="fa fa-plus"></i> Block
                                </button>
                            </div>
                        </div>
                        <div class="apx-hint">Leave both times empty to block the whole day.</div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
