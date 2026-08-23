<div class="apx">
    <x-property-appointment.premium />

    @once
        <style>
        .apx .apx-employee-select + .ts-wrapper .ts-control {
            border-radius: 9px;
            border-color: var(--border-strong);
            background: var(--surface);
            min-height: 34px;
            padding-block: 4px;
            font-size: 12.5px;
        }

        .apx .apx-employee-select + .ts-wrapper.focus .ts-control {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
        }

        /* The tabs card is overflow-hidden for its rounded corners, so the list
           is parented to <body> instead of being clipped inside it. Being out of
           the card also puts it out of the card's stacking context, hence the
           explicit width/position handling TomSelect does and the z-index that
           keeps it above the sticky page chrome. */
        .ts-dropdown.apx-employee-dropdown {
            z-index: 1080;
            font-size: 12.5px;
        }

        .ts-dropdown.apx-employee-dropdown .ts-dropdown-content {
            max-height: 320px;
        }
        </style>
    @endonce

    @php
        $rentOut = $this->rentOut;
        $appointment = $this->appointment;
        $employee = $this->employee;
        $customer = $appointment?->customer ?? $rentOut?->account;
    @endphp

    {{-- The employee is chosen HERE, on the appointment, not inherited from the
         agreement: whoever shows a property is routinely not whoever owns the
         lease. Everything below — the slot grid, the link, the diary the booking
         lands in — follows this one choice, so it leads the tab as a single
         strip rather than a panel of its own. --}}
    <div class="apx-ck mb-3 @if (! $employee) idle @endif">
        <div class="who">
            @if ($employee)
                <span class="apx-avatar">{{ \Illuminate\Support\Str::of($employee->name)->substr(0, 2)->upper() }}</span>
                <div>
                    <div class="nm">
                        {{-- Opens in a new tab: the booking in progress on this tab is not
                             worth losing to check somebody's diary. --}}
                        @can('employee.edit')
                            <a href="{{ route('users::employee::view', $employee->id) }}" target="_blank"
                                title="Open {{ $employee->name }}'s employee page">
                                {{ $employee->name }} <i class="fa fa-external-link"></i>
                            </a>
                        @else
                            {{ $employee->name }}
                        @endcan
                    </div>
                    <div class="sb">{{ $employee->mobile ?: 'No mobile on file' }}</div>
                </div>
            @else
                <span class="apx-avatar"><i class="fa fa-user-o"></i></span>
                <div>
                    <div class="nm">No employee assigned</div>
                    <div class="sb">Who will carry out this appointment?</div>
                </div>
            @endif
        </div>

        <div class="vr d-none d-sm-block"></div>

        <div class="picker" wire:ignore>
            <select id="apxEmployeeId" class="apx-employee-select" placeholder="Search and select employee…">
                <option value=""></option>
                @if ($employee)
                    <option value="{{ $employee->id }}" selected>{{ $employee->name }}</option>
                @endif
            </select>
        </div>

        <div class="rgt">
            @if ($appointment?->scheduled_at)
                {{-- The warning still has to be reachable, but not at the cost of a
                     paragraph on a strip: it hangs off the icon beside the picker. --}}
                <i class="fa fa-exclamation-circle" style="color:var(--warning)"
                    title="Changing this hands the confirmed slot to someone else — it is refused if their diary is not clear at that time."></i>
            @endif
            @if ($appointment)
                <span class="apx-chip chip-{{ $appointment->status }}">
                    <span class="dot"></span> {{ $appointment->statusLabel() }}
                </span>
            @elseif ($employee)
                <span class="apx-chip chip-scheduled"><span class="dot"></span> Assigned</span>
            @endif
        </div>
    </div>

    @if (! $employee)
        {{-- Nothing further can be offered: there is no diary to read slots
             from and nobody for the customer's link to belong to. --}}
        <div class="apx-prompt">
            <span class="ic"><i class="fa fa-paper-plane-o"></i></span>
            <div class="flex-grow-1">
                <div class="t">The scheduler opens as soon as somebody is chosen</div>
                <div class="s">
                    Slots come from this person's own weekly availability, or the company week from
                    Settings &rarr; Working Day when they have none of their own.
                </div>
            </div>
        </div>
    @elseif (! $appointment)
        <div class="apx-rec">
            <div class="apx-rec-h">
                <span class="apx-ico" style="width:34px;height:34px;border-radius:11px;font-size:14px">
                    <i class="fa fa-paper-plane-o"></i>
                </span>
                <div class="flex-grow-1">
                    <div class="tt">No appointment link sent yet</div>
                    <div class="ss">
                        Send {{ $rentOut->account?->name ?? 'the customer' }} a secure link and they choose from
                        {{ $employee->name }}'s availability &mdash; or the company week from Settings &rarr; Working Day
                        when they have none of their own.
                    </div>
                </div>
            </div>
            <div class="apx-facts">
                <div class="f">
                    <div class="k">Link valid until</div>
                    <div class="v">
                        <input type="date" class="form-control form-control-sm" wire:model="linkValidUntil"
                            style="max-width:180px">
                    </div>
                </div>
                <div class="f">
                    <div class="k">Email template</div>
                    <div class="v">
                        <i class="fa fa-envelope-o" style="color:var(--brand)"></i> Appointment Invitation
                        @can('email template.view')
                            <a href="{{ route('settings::email_template::index') }}" class="apx-hint"
                                style="margin-inline-start:4px">Manage</a>
                        @endcan
                    </div>
                </div>
            </div>
            @canany(['property appointment.send link', 'property appointment.create'])
            <div class="apx-bar">
                @can('property appointment.send link')
                    <button type="button" class="apx-btn apx-btn-primary apx-btn-xs" wire:click="sendLink"
                        wire:loading.attr="disabled">
                        <i class="fa fa-paper-plane"></i> Send appointment link
                    </button>
                @endcan
                @can('property appointment.create')
                    <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs" wire:click="$toggle('showSlotPicker')">
                        <i class="fa fa-calendar-o"></i> Book on their behalf
                    </button>
                @endcan
            </div>
            @endcanany
        </div>
    @else
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="apx-rec">
                    <div class="apx-rec-h">
                        @if ($appointment->scheduled_at)
                            <div class="apx-dt">
                                <div class="mo">{{ $appointment->scheduled_at->format('M') }}</div>
                                <div class="dy">{{ $appointment->scheduled_at->format('d') }}</div>
                            </div>
                        @else
                            <div class="apx-dt wait"><i class="fa fa-hourglass-half"></i></div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="tt">
                                {{ $appointment->scheduled_at ? appointmentTime($appointment->scheduled_at) : 'Not booked yet' }}
                            </div>
                            <div class="ss">
                                {{ $appointment->scheduled_at?->format('l') ?? "Customer picks from {$employee->name}'s availability" }}
                                &middot; {{ config('app.timezone') }}
                            </div>
                        </div>
                    </div>

                    {{-- Four facts in a 2×2 grid: the same record the four stacked
                         rows carried, in a quarter of the height. --}}
                    <div class="apx-facts">
                        <div class="f">
                            <div class="k">Reference</div>
                            <div class="v mono">{{ $appointment->reference_no }}</div>
                        </div>
                        <div class="f">
                            <div class="k">Property</div>
                            <div class="v">{{ $rentOut->property?->number ?? '—' }}</div>
                        </div>
                        <div class="f">
                            <div class="k">Customer</div>
                            <div class="v" title="{{ $customer?->name }}">{{ $customer?->name ?? '—' }}</div>
                        </div>
                        @if ($appointment->booked_at)
                            <div class="f">
                                <div class="k">Booked</div>
                                <div class="v">{{ $appointment->booked_at->format('d M, H:i') }} &middot; {{ $appointment->booked_by }}</div>
                            </div>
                        @else
                            <div class="f">
                                <div class="k">Mobile</div>
                                <div class="v">{{ $customer?->mobile ?: '—' }}</div>
                            </div>
                        @endif
                    </div>

                    @canany(['property appointment.create', 'property appointment.edit'])
                    <div class="apx-bar">
                        @can('property appointment.create')
                            <button type="button" class="apx-btn apx-btn-soft apx-btn-xs" wire:click="$toggle('showSlotPicker')">
                                <i class="fa fa-refresh"></i> {{ $appointment->scheduled_at ? 'Reschedule' : 'Book a slot' }}
                            </button>
                        @endcan
                        @can('property appointment.edit')
                            @if ($appointment->status === 'scheduled')
                                <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs" wire:click="markStatus('completed')">
                                    <i class="fa fa-check"></i> Completed
                                </button>
                                <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs" wire:click="markStatus('no_show')">
                                    <i class="fa fa-user-times"></i> No-show
                                </button>
                            @endif
                            <span class="spacer"></span>
                            <button type="button" class="apx-btn apx-btn-danger apx-btn-xs" wire:click="cancel"
                                wire:confirm="Cancel this appointment? The slot will be released.">
                                <i class="fa fa-times"></i> Cancel
                            </button>
                        @endcan
                    </div>
                    @endcanany
                </div>
            </div>

            <div class="col-lg-5">
                <div class="apx-rec">
                    <div class="apx-minih">
                        <span class="apx-sect">Link &amp; delivery</span>
                        @if ($appointment->link_opened_count)
                            <span class="rt apx-hint">{{ $appointment->link_opened_count }} open(s)</span>
                        @endif
                    </div>
                    <div class="apx-rec-b">
                        <div class="apx-linkbox mb-3" x-data="{ copied: false, copy() {
                                const url = this.$refs.url.textContent.trim();
                                const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1600); };
                                if (navigator.clipboard && window.isSecureContext) {
                                    navigator.clipboard.writeText(url).then(done).catch(() => this.fallback(url, done));
                                } else {
                                    this.fallback(url, done);
                                }
                            }, fallback(url, done) {
                                const el = document.createElement('textarea');
                                el.value = url; el.setAttribute('readonly', '');
                                el.style.position = 'fixed'; el.style.opacity = '0';
                                document.body.appendChild(el); el.select();
                                try { document.execCommand('copy'); done(); } catch (e) {}
                                document.body.removeChild(el);
                            } }">
                            <i class="fa fa-link" style="color:var(--brand)"></i>
                            <span x-ref="url">{{ route('property_appointment::public', $appointment->token) }}</span>
                            <button type="button" class="apx-copy" :class="copied ? 'ok' : ''" x-on:click="copy()"
                                :title="copied ? 'Copied' : 'Copy link'">
                                <i class="fa" :class="copied ? 'fa-check' : 'fa-files-o'"></i>
                                <span x-text="copied ? 'Copied' : 'Copy'"></span>
                            </button>
                        </div>

                        <div class="apx-timeline tight">
                            @if ($appointment->booked_at)
                                <div class="apx-tl ok">
                                    <div class="tt">Appointment confirmed</div>
                                    <div class="ts">{{ $appointment->booked_at->format('d M Y, H:i') }} &middot; by {{ $appointment->booked_by }}</div>
                                </div>
                            @endif
                            @if ($appointment->link_opened_at)
                                <div class="apx-tl on">
                                    <div class="tt">Link opened</div>
                                    <div class="ts">{{ $appointment->link_opened_at->format('d M Y, H:i') }} &middot; {{ $appointment->link_opened_count }} time(s)</div>
                                </div>
                            @endif
                            @forelse ($appointment->emailLogs->sortByDesc('id')->take(4) as $log)
                                <div class="apx-tl {{ $log->status === 'sent' ? 'ok' : ($log->status === 'failed' ? 'bad' : '') }}">
                                    <div class="tt">{{ $log->typeLabel() }} — {{ $log->statusLabel() }}</div>
                                    <div class="ts">
                                        {{ ($log->sent_at ?? $log->created_at)?->format('d M Y, H:i') }} &middot; {{ $log->to_email }}
                                        @if ($log->error)
                                            <br><span style="color:var(--danger)">{{ $log->error }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="apx-tl">
                                    <div class="tt">No email sent yet</div>
                                    <div class="ts">The link exists but has not been emailed.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @can('property appointment.send link')
                        <div class="apx-bar">
                            <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs flex-grow-1" wire:click="sendLink">
                                <i class="fa fa-paper-plane-o"></i> {{ $appointment->link_sent_at ? 'Resend' : 'Send' }} link
                            </button>
                            <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs flex-grow-1" wire:click="revokeLink"
                                wire:confirm="Revoke this link? The customer will no longer be able to open it.">
                                <i class="fa fa-ban"></i> Revoke
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    {{-- Staff-side slot picker (book on the customer's behalf / reschedule) --}}
    @if ($showSlotPicker && $employee)
        @php $slots = $this->slots; @endphp
        <div class="apx-panel mt-3">
            <div class="apx-panel-h">
                <span class="apx-ico"><i class="fa fa-clock-o"></i></span>
                <div class="flex-grow-1">
                    <h4>Choose a slot</h4>
                    <div class="sub">{{ $employee->name }}'s availability &middot; {{ config('app.timezone') }}</div>
                </div>
                <button type="button" class="apx-btn apx-btn-ghost apx-btn-xs" wire:click="$toggle('showSlotPicker')">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="apx-panel-b">
                @if (empty($slots))
                    <div class="apx-alert alert-info">
                        <i class="fa fa-info-circle lead"></i>
                        <div>
                            <div class="t">No slots available</div>
                            <div class="s">
                                {{ $employee->name }} has no bookable hours in the next
                                {{ \App\Services\PropertyAppointment\SlotService::appointmentWindowDays() }} days.
                                Check the company hours in Settings → Working Day, or set this employee's own
                                weekly availability on their employee page.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="d-flex gap-2 flex-wrap mb-3" style="overflow-x:auto">
                        @foreach (array_slice(array_keys($slots), 0, 14) as $day)
                            <button type="button"
                                class="apx-daybtn {{ ($selectedDate ?: array_key_first($slots)) === $day ? 'sel' : '' }}"
                                wire:click="$set('selectedDate', '{{ $day }}')">
                                <div class="dw">{{ \Carbon\Carbon::parse($day)->format('D') }}</div>
                                <div class="dd">{{ \Carbon\Carbon::parse($day)->format('d') }}</div>
                                <div class="dc">{{ count($slots[$day]) }} open</div>
                            </button>
                        @endforeach
                    </div>

                    @php $activeDay = $selectedDate ?: array_key_first($slots); @endphp
                    <div class="apx-slots">
                        @foreach ($slots[$activeDay] ?? [] as $slot)
                            <button type="button"
                                class="apx-slot {{ $selectedSlot === $slot['value'] ? 'sel' : '' }}"
                                wire:click="$set('selectedSlot', '{{ $slot['value'] }}')">
                                {{ $slot['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="apx-btn apx-btn-primary" wire:click="bookSlot"
                            @disabled(blank($selectedSlot))>
                            <i class="fa fa-check-circle"></i> Confirm appointment
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@script
<script>
    /*
     * The whole body is one IIFE on purpose. Livewire hands a script block to
     * Alpine.evaluate() as an EXPRESSION, and Alpine only wraps it in an async
     * IIFE of its own when the text literally starts with `let`, `const` or
     * `if (`. Anything else — a comment first, as this block once had — is
     * compiled as `__self.result = <body>` and dies on the first statement with
     * "Unexpected token 'const'". An IIFE is a valid expression either way.
     *
     * Nothing in here may contain an at-sign followed by a directive name
     * either: Blade parses the comment too, and the word for this very block
     * written out in full closed it early and rendered the component empty.
     *
     * Inside: the picker sits behind wire:ignore so TomSelect survives every
     * re-render, which also means Livewire never sees the change itself — hence
     * the manual $wire.set. The sync event is the other direction: when the
     * server refuses a reassignment (the new person's diary is not clear) it
     * sends back who is ACTUALLY on the appointment, and the control is put back
     * silently so the name on screen is never a person the appointment lacks.
     */
    (() => {
        const el = document.getElementById('apxEmployeeId');

        if (el && !el.tomselect) {
            new TomSelect(el, {
                persist: false,
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'mobile', 'email', 'id'],
                // The tabs card is overflow-hidden, which would cut the list off at
                // its edge. Parenting to <body> takes it out of that box entirely.
                dropdownParent: 'body',
                dropdownClass: 'ts-dropdown apx-employee-dropdown',
                load: function(query, callback) {
                    fetch("{{ route('users::list') }}?type=employee&query=" + encodeURIComponent(query))
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(json => callback(json.items))
                        .catch(() => callback());
                },
                onFocus: function() {
                    this.clearOptions();
                    this.load('');
                },
                onChange: function(value) {
                    if (this._syncing) return;
                    $wire.set('employee_id', value || '');
                },
                render: {
                    option: (item, escape) =>
                        `<div>${escape(item.name || item.text || '')}${item.mobile ? ` @${escape(item.mobile)}` : ''}</div>`,
                    item: (item, escape) => `<div>${escape(item.name || item.text || '')}</div>`,
                },
            });
        }

        $wire.on('appointment-employee-synced', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            const control = document.getElementById('apxEmployeeId')?.tomselect;
            if (!control || String(control.getValue() || '') === String(data.id || '')) return;

            control._syncing = true;
            if (data.id) {
                control.addOption({ id: data.id, name: data.name });
            }
            control.setValue(data.id || '', true);
            control._syncing = false;
        });
    })();
</script>
@endscript
