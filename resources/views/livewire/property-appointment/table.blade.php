<div class="apx">
    <x-property-appointment.premium />

    <div class="apx-hero mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="apx-pill"><i class="fa fa-list-ul"></i> RentOut &middot; Sale</span>
                <h1 class="apx-hero-title mt-2 text-white">Appointments</h1>
                <div class="apx-hero-meta mt-1">Customer-scheduled property appointments for lease / sale agreements</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('property appointment.calendar')
                    <a href="{{ route('property::sale::appointment_schedule::calendar') }}" class="apx-btn apx-btn-glass">
                        <i class="fa fa-calendar"></i> Calendar view
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="apx-kpi mb-3">
        <div class="k accent"><div class="lb">Upcoming</div><div class="vl">{{ $stats['upcoming'] }}</div><div class="dl">confirmed, still ahead</div></div>
        <div class="k"><div class="lb">Awaiting</div><div class="vl">{{ $stats['awaiting'] }}</div><div class="dl">link sent, no slot picked</div></div>
        <div class="k"><div class="lb">Completed</div><div class="vl">{{ $stats['completed'] }}</div><div class="dl">appointment carried out</div></div>
        <div class="k"><div class="lb">No-shows</div><div class="vl">{{ $stats['no_show'] }}</div><div class="dl">customer did not attend</div></div>
    </div>

    <div class="apx-panel">
        <div class="apx-panel-h flex-wrap gap-2">
            <input type="text" class="form-control form-control-sm" style="max-width:220px"
                placeholder="Search reference, customer, property…" wire:model.live.debounce.400ms="search">

            <select class="form-select form-select-sm" style="max-width:150px" wire:model.live="status">
                <option value="">All statuses</option>
                <option value="awaiting">Awaiting customer</option>
                <option value="scheduled">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="no_show">No-show</option>
            </select>

            <select class="form-select form-select-sm" style="max-width:160px" wire:model.live="salesman_id">
                <option value="">All salesmen</option>
                @foreach ($salesmen as $salesman)
                    <option value="{{ $salesman->id }}">{{ $salesman->name }}</option>
                @endforeach
            </select>

            <input type="date" class="form-control form-control-sm" style="max-width:150px" wire:model.live="from_date">
            <input type="date" class="form-control form-control-sm" style="max-width:150px" wire:model.live="to_date">

            <div class="flex-grow-1"></div>

            @can('property appointment.delete')
                <button type="button" class="apx-btn apx-btn-danger" wire:click="delete"
                    wire:confirm="Delete the selected appointments?">
                    <i class="fa fa-trash"></i> Delete
                </button>
            @endcan
        </div>

        <div class="table-responsive">
            <table class="apx-tbl">
                <thead>
                    <tr>
                        <th style="width:34px"><input type="checkbox" wire:model.live="selectAll"></th>
                        <th>Reference</th>
                        <th style="cursor:pointer" wire:click="sortBy('property_appointments.scheduled_at')">
                            When {!! sortDirection($sortDirection) !!}
                        </th>
                        <th>Customer</th>
                        <th>Property</th>
                        <th>Agreement</th>
                        <th>Salesman</th>
                        <th>Status</th>
                        <th style="width:78px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr wire:key="appointment-{{ $appointment->id }}">
                            <td><input type="checkbox" value="{{ $appointment->id }}" wire:model.live="selected"></td>
                            <td><span class="ref">{{ $appointment->reference_no }}</span></td>
                            <td>
                                @if ($appointment->scheduled_at)
                                    <div class="strong">{{ $appointment->scheduled_at->format('D d M') }}</div>
                                    <div class="dim">{{ appointmentTime($appointment->scheduled_at) }}</div>
                                @else
                                    <div class="dim">Not booked yet</div>
                                    <div class="dim">
                                        {{ $appointment->link_sent_at ? 'link sent '.$appointment->link_sent_at->format('d M') : 'link not sent' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="who">
                                    <span class="apx-avatar">{{ \Illuminate\Support\Str::of($appointment->customer?->name)->substr(0, 2)->upper() }}</span>
                                    <div>
                                        <div class="strong">{{ $appointment->customer?->name }}</div>
                                        <div class="dim">{{ $appointment->customer?->mobile }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="strong">{{ $appointment->rentOut?->property?->number ?? '—' }}</div></td>
                            <td><span class="ref">#{{ $appointment->rent_out_id }}</span></td>
                            <td>
                                <div class="who">
                                    <span class="apx-avatar">{{ \Illuminate\Support\Str::of($appointment->salesman?->name)->substr(0, 2)->upper() }}</span>
                                    <span>{{ $appointment->salesman?->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="apx-chip chip-{{ $appointment->status }}">
                                    <span class="dot"></span> {{ $appointment->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('rent out lease.view')
                                        <a class="apx-btn apx-btn-ghost" style="padding:4px 8px"
                                            href="{{ route('property::sale::view', $appointment->rent_out_id) }}" title="Open agreement">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    @endcan
                                    @can('property appointment.edit')
                                        @if (in_array($appointment->status, ['awaiting', 'scheduled'], true))
                                            <button type="button" class="apx-btn apx-btn-ghost" style="padding:4px 8px"
                                                wire:click="cancel({{ $appointment->id }})"
                                                wire:confirm="Cancel this appointment? The slot will be released."
                                                title="Cancel">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="apx-empty">
                                    <div class="art"><i class="fa fa-calendar-o"></i></div>
                                    <h3>No appointments found</h3>
                                    <p>Nothing matches these filters. Appointments are created from a lease/sale agreement's
                                        Appointments tab by sending the customer a appointment link.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-2">
            {{ $appointments->links() }}
        </div>
    </div>
</div>
