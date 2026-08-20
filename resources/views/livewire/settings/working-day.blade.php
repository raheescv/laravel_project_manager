<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">Working Day Configuration</h4>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info rounded-0 border-0 border-bottom mb-0 py-3 px-4">
                        <i class="fa fa-info-circle me-2"></i>
                        These are the company's hours. Appointment slots are offered from them for every
                        salesman who has no weekly availability of their own — set a salesman's hours on their
                        employee page only when they differ from the week below.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 fw-bold text-uppercase ps-4">Day</th>
                                    <th class="text-center py-3 fw-bold text-uppercase">Status</th>
                                    <th class="py-3 fw-bold text-uppercase">From</th>
                                    <th class="py-3 fw-bold text-uppercase pe-4">To</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (! $days)
                                    {{-- Nothing to configure yet: the scheduler is quietly running on the
                                         module defaults, so offer to make that week real and editable. --}}
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="mb-2 text-muted">
                                                <i class="fa fa-calendar-o fa-2x"></i>
                                            </div>
                                            <h5 class="fw-bold mb-1">No working days configured</h5>
                                            <p class="text-muted mb-3">
                                                Appointment slots currently follow the built-in default week
                                                ({{ $defaults['start_time'] ?? '09:00' }}–{{ $defaults['end_time'] ?? '18:00' }}).
                                                Create it here to make it yours.
                                            </p>
                                            <button wire:click="createDefaultWeek" type="button" class="btn btn-primary px-4">
                                                <i class="fa fa-magic me-2"></i>Create the default working week
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                                @foreach($days as $index => $day)
                                <tr wire:key="working-day-{{ $day['id'] }}">
                                    <td class="py-3 fw-semibold ps-4">
                                        {{ $day['day_name'] }}
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="form-check form-switch d-flex justify-content-center align-items-center">
                                            <input class="form-check-input me-2"
                                                   type="checkbox"
                                                   wire:model.live="days.{{ $index }}.is_working"
                                                   id="day_{{ $day['id'] }}"
                                                   role="switch">
                                            <label class="form-check-label fw-bold {{ $day['is_working'] ? 'text-success' : 'text-danger' }}"
                                                   for="day_{{ $day['id'] }}">
                                                {{ $day['is_working'] ? 'ON' : 'OFF' }}
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3" style="min-width:130px">
                                        <input type="time" class="form-control form-control-sm"
                                               wire:model="days.{{ $index }}.start_time"
                                               @disabled(! $day['is_working'])>
                                    </td>
                                    <td class="py-3 pe-4" style="min-width:130px">
                                        <input type="time" class="form-control form-control-sm"
                                               wire:model="days.{{ $index }}.end_time"
                                               @disabled(! $day['is_working'])>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($days)
                    <div class="card-footer bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <button wire:click="applyToAll" type="button" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-copy me-2"></i>Apply the first working day's hours to every day
                        </button>
                        <button wire:click="updateSettings" class="btn btn-primary px-4">
                            <i class="fa fa-save me-2"></i>Update Settings
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
