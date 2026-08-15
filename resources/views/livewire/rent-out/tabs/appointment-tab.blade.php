<div class="apx">
    <x-property-appointment.premium />

    @php
        $rentOut = $this->rentOut;
        $appointment = $this->appointment;
    @endphp

    @if (! $rentOut?->salesman_id)
        {{-- salesman_id is nullable on rent_outs: with no salesman there is no
             availability to offer, so the scheduler cannot run at all. --}}
        <div class="apx-alert alert-warn">
            <i class="fa fa-user-times lead"></i>
            <div>
                <div class="t">No salesman on this agreement</div>
                <div class="s">
                    <code class="apx-codev">salesman_id</code> is empty, so there is no availability to offer and
                    nobody to carry out the appointment. Assign a salesman to this agreement and the scheduler
                    becomes available here.
                </div>
            </div>
        </div>
    @elseif (! $appointment)
        <div class="apx-empty">
            <div class="art"><i class="fa fa-paper-plane-o"></i></div>
            <h3>No appointment link sent yet</h3>
            <p>
                Send {{ $rentOut->account?->name ?? 'the customer' }} a secure link and they will choose a time
                from {{ $rentOut->salesman?->name }}'s availability. The confirmed slot appears here as soon as they book.
            </p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                @can('property appointment.send link')
                    <button type="button" class="apx-btn apx-btn-primary" wire:click="sendLink" wire:loading.attr="disabled">
                        <i class="fa fa-paper-plane"></i> Send appointment link
                    </button>
                @endcan
                @can('property appointment.create')
                    <button type="button" class="apx-btn apx-btn-ghost" wire:click="$toggle('showSlotPicker')">
                        <i class="fa fa-calendar-plus-o"></i> Book on their behalf
                    </button>
                @endcan
            </div>
        </div>

        <div class="row g-3 mt-1 pt-3" style="border-top:1px solid var(--border)">
            <div class="col-md-5">
                <div class="apx-sect">Salesman</div>
                <div class="apx-derived">
                    <span class="apx-avatar">{{ \Illuminate\Support\Str::of($rentOut->salesman?->name)->substr(0, 2)->upper() }}</span>
                    <div class="flex-grow-1">
                        <div style="font-size:12.5px;font-weight:700">{{ $rentOut->salesman?->name }}</div>
                        <div style="font-size:10.5px;color:var(--text-3)">{{ $rentOut->salesman?->mobile }}</div>
                    </div>
                    <span class="apx-chip chip-cancelled"><i class="fa fa-lock"></i> From agreement</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="apx-sect">Link valid until</div>
                <input type="date" class="form-control form-control-sm" wire:model="linkValidUntil">
            </div>
            <div class="col-md-4">
                <div class="apx-sect">Email template</div>
                <div class="apx-derived">
                    <i class="fa fa-envelope-o" style="color:var(--brand)"></i>
                    <div class="flex-grow-1" style="font-size:11.5px">Active <b>Appointment Invitation</b> template</div>
                </div>
                <div class="apx-hint">
                    @can('email template.view')
                        <a href="{{ route('settings::email_template::index') }}">Manage email templates</a>
                    @else
                        Managed under Settings &rarr; Email Templates.
                    @endcan
                </div>
            </div>
        </div>
    @else
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="apx-bookcard">
                    <div class="bc-h">
                        @if ($appointment->scheduled_at)
                            <div class="bc-date">
                                <div class="mo">{{ $appointment->scheduled_at->format('M') }}</div>
                                <div class="dy">{{ $appointment->scheduled_at->format('d') }}</div>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="bc-t">
                                {{ $appointment->scheduled_at ? appointmentTime($appointment->scheduled_at) : 'Not booked yet' }}
                            </div>
                            <div class="bc-s">
                                {{ $appointment->scheduled_at?->format('l') }}
                                &middot; {{ config('app.timezone') }}
                            </div>
                        </div>
                        <span class="apx-chip chip-{{ $appointment->status }}" style="background:rgba(255,255,255,.92)">
                            <span class="dot"></span> {{ $appointment->statusLabel() }}
                        </span>
                    </div>
                    <div class="bc-b">
                        <div class="apx-kv"><div class="k">Reference</div><div class="v">{{ $appointment->reference_no }}</div></div>
                        <div class="apx-kv"><div class="k">Customer</div><div class="v">{{ $appointment->customer?->name }} &middot; {{ $appointment->customer?->mobile }}</div></div>
                        <div class="apx-kv"><div class="k">Salesman</div><div class="v">{{ $appointment->salesman?->name }}</div></div>
                        <div class="apx-kv"><div class="k">Property</div><div class="v">{{ $rentOut->property?->number }}</div></div>
                        @if ($appointment->booked_at)
                            <div class="apx-kv">
                                <div class="k">Booked at</div>
                                <div class="v">{{ $appointment->booked_at->format('d M Y, H:i') }} &middot; by {{ $appointment->booked_by }}</div>
                            </div>
                        @endif
                    </div>
                    <div class="bc-f">
                        @can('property appointment.create')
                            <button type="button" class="apx-btn apx-btn-soft" wire:click="$toggle('showSlotPicker')">
                                <i class="fa fa-refresh"></i> {{ $appointment->scheduled_at ? 'Reschedule' : 'Book a slot' }}
                            </button>
                        @endcan
                        @can('property appointment.edit')
                            @if ($appointment->status === 'scheduled')
                                <button type="button" class="apx-btn apx-btn-ghost" wire:click="markStatus('completed')">
                                    <i class="fa fa-check"></i> Mark completed
                                </button>
                                <button type="button" class="apx-btn apx-btn-ghost" wire:click="markStatus('no_show')">
                                    <i class="fa fa-user-times"></i> No-show
                                </button>
                            @endif
                            <button type="button" class="apx-btn apx-btn-danger" wire:click="cancel"
                                wire:confirm="Cancel this appointment? The slot will be released.">
                                <i class="fa fa-times"></i> Cancel
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="apx-panel">
                    <div class="apx-panel-h">
                        <span class="apx-ico"><i class="fa fa-history"></i></span>
                        <div class="flex-grow-1">
                            <h4>Link &amp; delivery</h4>
                            <div class="sub">What the customer has seen</div>
                        </div>
                    </div>
                    <div class="apx-panel-b">
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

                        <div class="apx-timeline">
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
                            @forelse ($appointment->emailLogs->sortByDesc('id')->take(5) as $log)
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

                        @can('property appointment.send link')
                            <div class="d-flex gap-2 mt-3">
                                <button type="button" class="apx-btn apx-btn-ghost apx-btn-block" wire:click="sendLink">
                                    <i class="fa fa-paper-plane-o"></i> {{ $appointment->link_sent_at ? 'Resend' : 'Send' }} link
                                </button>
                                <button type="button" class="apx-btn apx-btn-ghost apx-btn-block" wire:click="revokeLink"
                                    wire:confirm="Revoke this link? The customer will no longer be able to open it.">
                                    <i class="fa fa-ban"></i> Revoke
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Staff-side slot picker (book on the customer's behalf / reschedule) --}}
    @if ($showSlotPicker && $rentOut?->salesman_id)
        @php $slots = $this->slots; @endphp
        <div class="apx-panel mt-3">
            <div class="apx-panel-h">
                <span class="apx-ico"><i class="fa fa-clock-o"></i></span>
                <div class="flex-grow-1">
                    <h4>Choose a slot</h4>
                    <div class="sub">{{ $rentOut->salesman?->name }}'s availability &middot; {{ config('app.timezone') }}</div>
                </div>
                <button type="button" class="apx-btn apx-btn-ghost" wire:click="$toggle('showSlotPicker')">
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
                                {{ $rentOut->salesman?->name }} has no bookable hours in the next
                                {{ \App\Services\PropertyAppointment\SlotService::appointmentWindowDays() }} days.
                                Set their weekly availability on their employee page first.
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
                            <i class="fa fa-calendar-check-o"></i> Confirm appointment
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
