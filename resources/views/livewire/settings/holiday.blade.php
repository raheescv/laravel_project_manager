<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0 text-white">Holiday Calendar</h4>
                    <div class="d-flex align-items-center gap-2">
                        <label for="holidayYear" class="text-white small mb-0">Year</label>
                        <select id="holidayYear" class="form-select form-select-sm" style="width:auto"
                                wire:model.live="year">
                            @foreach ($this->years as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info rounded-0 border-0 border-bottom mb-0 py-3 px-4">
                        <i class="fa fa-info-circle me-2"></i>
                        These dates close the business for everyone. A holiday overrides the working week —
                        a date listed here offers no appointment slots even when its weekday is switched on in
                        Working Day, and it is shaded on the appointment calendar.
                        Use an employee's own <strong>Time off</strong> when only one person is away.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 fw-bold text-uppercase ps-4">Date</th>
                                    <th class="py-3 fw-bold text-uppercase">Holiday</th>
                                    <th class="text-center py-3 fw-bold text-uppercase">Repeats</th>
                                    <th class="text-center py-3 fw-bold text-uppercase">Status</th>
                                    <th class="text-end py-3 fw-bold text-uppercase pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($holidays as $row)
                                    @php($holiday = $row['model'])
                                    <tr wire:key="holiday-{{ $holiday->id }}"
                                        class="{{ $holiday->is_active ? '' : 'opacity-50' }}">
                                        <td class="py-3 ps-4">
                                            <div class="fw-semibold">{{ $row['occurs']->format('d M Y') }}</div>
                                            <div class="text-muted small">{{ $row['occurs']->format('l') }}</div>
                                        </td>
                                        <td class="py-3">
                                            <div class="fw-semibold">{{ $holiday->name }}</div>
                                            @if ($holiday->note)
                                                <div class="text-muted small">{{ $holiday->note }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center py-3">
                                            @if ($holiday->is_recurring)
                                                <span class="badge bg-info-subtle text-info-emphasis">
                                                    <i class="fa fa-repeat me-1"></i>Every year
                                                </span>
                                            @else
                                                <span class="text-muted small">One-off</span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3">
                                            <button type="button"
                                                    class="btn btn-sm {{ $holiday->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                                    wire:click="toggleActive({{ $holiday->id }})"
                                                    title="{{ $holiday->is_active ? 'Switch this holiday off' : 'Switch this holiday back on' }}">
                                                {{ $holiday->is_active ? 'ON' : 'OFF' }}
                                            </button>
                                        </td>
                                        <td class="text-end py-3 pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    wire:click="edit({{ $holiday->id }})">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="remove({{ $holiday->id }})"
                                                    wire:confirm="Delete {{ $holiday->name }}? Appointments can be booked on that date again.">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="mb-2 text-muted"><i class="fa fa-calendar-o fa-2x"></i></div>
                                            <h5 class="fw-bold mb-1">No holidays in {{ $year }}</h5>
                                            <p class="text-muted mb-0">
                                                Every working day in {{ $year }} is open for appointments.
                                                Add a closure below.
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-light py-3">
                    <div class="fw-bold mb-2">
                        <i class="fa fa-{{ $editingId ? 'pencil' : 'plus' }} me-2"></i>
                        {{ $editingId ? 'Edit holiday' : 'Add a holiday' }}
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Name</label>
                            <input type="text" class="form-control form-control-sm" wire:model="name"
                                   placeholder="e.g. National Day">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Date</label>
                            <input type="date" class="form-control form-control-sm" wire:model="date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Note (optional)</label>
                            <input type="text" class="form-control form-control-sm" wire:model="note"
                                   placeholder="Shown to staff only">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="holidayRecurring" wire:model="is_recurring">
                                <label class="form-check-label small" for="holidayRecurring">Every year</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-primary px-4" wire:click="save">
                                <i class="fa fa-save me-2"></i>{{ $editingId ? 'Update holiday' : 'Add holiday' }}
                            </button>
                            @if ($editingId)
                                <button type="button" class="btn btn-outline-secondary" wire:click="cancelEdit">
                                    Cancel
                                </button>
                            @endif
                            <span class="ms-auto align-self-center text-muted small">
                                {{ $upcoming }} upcoming {{ \Illuminate\Support\Str::plural('closure', $upcoming) }}
                            </span>
                        </div>
                    </div>
                    <div class="form-text mt-2">
                        Tick <strong>Every year</strong> for a fixed date such as National Day. Leave it clear for
                        anything that moves — Eid and the like have to be entered each year.
                        A closure lasting several days is added as one row per day.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
