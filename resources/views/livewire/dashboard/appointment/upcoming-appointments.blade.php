<div class="card h-100">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <a href="{{ route('appointment::list') }}" class="btn btn-sm btn-light">
                Upcoming Appointments
            </a>
        </h5>
    </div>
    <div class="card-body">
        <div class="list-group list-group-flush">
            @forelse($appointments as $item)
                <div class="list-group-item px-0" wire:key="{{ $item->id }}">
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">
                                <i class="demo-psi-calendar-4 fs-5 me-2" style="color: {{ $item->appointment->color }}"></i>
                                {{ $item->service->name }}
                            </h6>
                            <small class="text-muted">
                                with {{ $item->employee->name }}
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-dark">
                                {{ Carbon\Carbon::parse($item->appointment->start_time)->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <i class="demo-psi-male fs-5 me-2 text-primary"></i>
                            {{ $item->appointment->account->name }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <i class="demo-psi-calendar-4 fs-1 text-muted"></i>
                    <p class="mt-2 mb-0">No upcoming appointments</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
