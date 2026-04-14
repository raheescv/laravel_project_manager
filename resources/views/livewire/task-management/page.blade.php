<div>
    @if ($showModal)
        <div class="modal-header">
            <div class="d-flex align-items-center gap-2">
                <h5 class="modal-title mb-0">{{ $modalMode === 'create' ? 'New Task' : ($modalMode === 'edit' ? 'Edit Task' : 'View Task') }}</h5>
                @if ($modalMode !== 'create')
                    <select class="form-select form-select-sm" style="width: 160px;" wire:model="form.status" @if ($modalMode === 'view') disabled @endif>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if ($modalMode === 'view' && auth()->user()->can('task-management.edit') && $activeTaskId)
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="setModalMode('edit')">Edit</button>
                @endif
                <button type="button" class="btn-close" wire:click="closeModal"></button>
            </div>
        </div>

        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach ($this->getErrorBag()->toArray() as $errors)
                            <li>{{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model="form.name" @if ($modalMode === 'view') disabled @endif autofocus>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type</label>
                    <input type="text" class="form-control" wire:model="form.type" placeholder="e.g. Bug, Feature" @if ($modalMode === 'view') disabled @endif>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Project</label>
                    <input type="text" class="form-control" wire:model="form.project" placeholder="Project name" @if ($modalMode === 'view') disabled @endif>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Assigned To <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model="form.assigned_to" @if ($modalMode === 'view') disabled @endif>
                        <option value="">Select user</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model="form.priority" @if ($modalMode === 'view') disabled @endif>
                        <option value="">Select priority</option>
                        @foreach ($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Due Date</label>
                    <input type="date" class="form-control" wire:model="form.end_date" @if ($modalMode === 'view') disabled @endif>
                </div>
                @if ($modalMode === 'create')
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" wire:model="form.status">
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="4" wire:model="form.description" @if ($modalMode === 'view') disabled @endif placeholder="Describe the task..."></textarea>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Close</button>
            @if (($modalMode === 'create' && auth()->user()->can('task-management.create')) || ($modalMode === 'edit' && auth()->user()->can('task-management.edit')))
                <button type="button" class="btn btn-primary" wire:click="save">
                    <i class="fa fa-check me-1"></i> Save
                </button>
            @endif
        </div>
    @endif
</div>
