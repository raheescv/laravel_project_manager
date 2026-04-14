<div>
    @if($maintenance)
        {{-- Maintenance Header Card --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-wrench me-2 text-primary"></i>
                    Maintenance Request #{{ $maintenance->id }}
                </h6>
                <span class="badge bg-{{ $maintenance->status->color() }} fs-6">{{ $maintenance->status->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Property</label>
                        <p class="fw-medium mb-0">{{ $maintenance->property?->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Building</label>
                        <p class="fw-medium mb-0">{{ $maintenance->building?->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Customer</label>
                        <p class="fw-medium mb-0">{{ $maintenance->customer?->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Date</label>
                        <p class="fw-medium mb-0">{{ $maintenance->date?->format('d M Y') }} {{ $maintenance->time ?? '' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Priority</label>
                        <p class="mb-0"><span class="badge bg-{{ $maintenance->priority?->color() }}">{{ $maintenance->priority?->label() }}</span></p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Segment</label>
                        <p class="mb-0">
                            @if($maintenance->segment)
                                <span class="badge bg-{{ $maintenance->segment->color() }}">{{ $maintenance->segment->label() }}</span>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Contact No</label>
                        <p class="fw-medium mb-0">{{ $maintenance->contact_no ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-0">Created By</label>
                        <p class="fw-medium mb-0">{{ $maintenance->creator?->name ?? '-' }}</p>
                    </div>
                    @if($maintenance->remark)
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-0">Remark</label>
                            <p class="mb-0">{{ $maintenance->remark }}</p>
                        </div>
                    @endif
                    @if($maintenance->company_remark)
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-0">Company Remark</label>
                            <p class="mb-0">{{ $maintenance->company_remark }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Complaints & Technician Assignment Card --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa fa-users me-2 text-info"></i>Complaint Assignments</h6>
                @can('maintenance.complete')
                    @if($maintenance->status->value !== 'completed' && $maintenance->status->value !== 'cancelled')
                        <button type="button" wire:click="completeMaintenance" wire:confirm="Are you sure you want to mark this maintenance as completed?"
                            class="btn btn-sm btn-success">
                            <i class="fa fa-check me-1"></i> Complete Maintenance
                        </button>
                    @endif
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted">
                            <tr class="small">
                                <th class="fw-semibold">#</th>
                                <th class="fw-semibold">Complaint</th>
                                <th class="fw-semibold">Category</th>
                                <th class="fw-semibold text-center">Status</th>
                                <th class="fw-semibold">Technician</th>
                                <th class="fw-semibold">Remark</th>
                                <th class="fw-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complaintData as $index => $complaint)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-medium">{{ $complaint['complaint_name'] }}</td>
                                    <td>{{ $complaint['category_name'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $complaint['status_color'] }}">{{ $complaint['status_label'] }}</span>
                                    </td>
                                    <td>
                                        @if(in_array($complaint['status'], ['pending', 'assigned']))
                                            <select class="form-select form-select-sm" wire:model="complaintData.{{ $index }}.technician_id" style="min-width: 150px">
                                                <option value="">Select Technician</option>
                                                @foreach($technicians as $tech)
                                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{ $complaint['technician_name'] ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($complaint['status'], ['assigned']))
                                            <input type="text" class="form-control form-control-sm" wire:model="complaintData.{{ $index }}.technician_remark" placeholder="Remark...">
                                        @else
                                            {{ $complaint['technician_remark'] ?: '-' }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            @if(in_array($complaint['status'], ['pending']))
                                                @can('maintenance.assign')
                                                    <button type="button" wire:click="assignTechnician({{ $index }})" class="btn btn-sm btn-info" title="Assign" data-bs-toggle="tooltip">
                                                        <i class="fa fa-user-plus"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                            @if(in_array($complaint['status'], ['assigned']))
                                                @can('maintenance.complete')
                                                    <button type="button" wire:click="completeComplaint({{ $index }})" wire:confirm="Mark this complaint as completed?"
                                                        class="btn btn-sm btn-success" title="Complete" data-bs-toggle="tooltip">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa fa-info-circle me-1"></i>No complaints found for this maintenance request.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Back Button --}}
        <div class="d-flex justify-content-start mb-4">
            <a href="{{ route('property::maintenance::index') }}" class="btn btn-light">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    @else
        <div class="alert alert-danger">Maintenance request not found.</div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, { boundary: document.body });
                });
            });
        </script>
    @endpush
</div>
