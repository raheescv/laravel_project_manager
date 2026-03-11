<div class="task-board-wrapper" x-data="{ draggedId: null }">
    <style>
        .task-board-wrapper {
            background:
                radial-gradient(1200px 400px at 10% 0%, rgba(var(--bs-primary-rgb), 0.14), transparent 60%),
                radial-gradient(900px 500px at 90% 10%, rgba(var(--bs-info-rgb), 0.1), transparent 55%),
                var(--bs-light);
            border: 1px solid var(--bs-border-color);
            border-radius: 16px;
            overflow: hidden;
        }

        .task-board-top {
            background: rgba(var(--bs-primary-rgb), 0.06);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .task-column {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: 16px;
            min-height: 420px;
        }

        .task-card {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: 14px;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .task-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(var(--bs-primary-rgb), .12);
            border-color: rgba(var(--bs-primary-rgb), 0.5);
        }

        .task-card .task-actions { opacity: 0; transition: opacity .2s ease; }
        .task-card:hover .task-actions { opacity: 1; }
    </style>

    {{-- Top bar --}}
    <div class="task-board-top p-3 p-md-4">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex gap-2 align-items-center">
                <h5 class="mb-0 text-primary">Task Management</h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn {{ $viewMode === 'board' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setViewMode('board')">
                        <i class="demo-pli-layout-grid me-1"></i> Board
                    </button>
                    <button type="button" class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setViewMode('table')">
                        <i class="demo-pli-list-view me-1"></i> Table
                    </button>
                </div>
            </div>
            <div class="d-flex gap-2">
                @can('task-management.create')
                    <button type="button" class="btn btn-sm btn-primary" wire:click="openCreateModal">
                        <i class="demo-psi-add me-1"></i> Add Task
                    </button>
                @endcan
                @if ($viewMode === 'table')
                    @can('task-management.delete')
                        <button class="btn btn-sm btn-outline-danger" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected tasks?">
                            <i class="demo-pli-recycling me-1"></i> Delete
                        </button>
                    @endcan
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <div class="row g-2 mt-2">
            <div class="col-md-3">
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search tasks...">
            </div>
            <div class="col-md-2">
                <select wire:model.live="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select wire:model.live="priority" class="form-select form-select-sm">
                    <option value="">All Priority</option>
                    @foreach ($priorityOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100"
                    wire:click="$set('status', ''); $set('search', ''); $set('priority', ''); $set('from_date', '{{ date('Y-m-01') }}'); $set('to_date', '{{ date('Y-m-d') }}');">
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Board View --}}
    @if ($viewMode === 'board')
        <div class="p-3 p-md-4">
            <div class="row g-3">
                @foreach ($statuses as $statusKey => $statusLabel)
                    <div class="col-md-6 col-xl-3">
                        <div class="task-column d-flex flex-column h-100">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                <strong class="text-primary">{{ $statusLabel }}</strong>
                                <span class="badge bg-secondary">{{ count($boardData->get($statusKey, [])) }}</span>
                            </div>
                            <div class="p-2 flex-grow-1" style="min-height: 320px;" @dragover.prevent
                                @drop.prevent="if (draggedId) { $wire.moveStatus(draggedId, '{{ $statusKey }}'); draggedId = null; }">
                                @forelse ($boardData->get($statusKey, collect()) as $task)
                                    <div class="task-card mb-2" @can('task-management.edit') draggable="true" @dragstart="draggedId = {{ $task->id }}" @endcan>
                                        <div class="p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <button type="button" class="btn btn-link p-0 text-start text-decoration-none fw-semibold text-body"
                                                    wire:click="openViewModal({{ $task->id }})">
                                                    {{ $task->name }}
                                                </button>
                                                <div class="task-actions d-flex gap-1">
                                                    @can('task-management.edit')
                                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" wire:click="openEditModal({{ $task->id }})" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1" wire:click="$dispatch('task-notes-open', { taskId: {{ $task->id }} })" title="Notes">
                                                        <i class="fa fa-comments-o"></i>
                                                    </button>
                                                    @can('task-management.delete')
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" wire:click="deleteSingle({{ $task->id }})" wire:confirm="Delete this task?" title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </div>
                                            <div class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</div>
                                            <div class="d-flex flex-wrap gap-2 align-items-center small">
                                                @if ($task->type)
                                                    <span class="badge bg-danger bg-opacity-10 text-danger">{{ $task->type }}</span>
                                                @endif
                                                @if ($task->project)
                                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $task->project }}</span>
                                                @endif
                                                <span class="badge bg-{{ $task->priority === 'Critical' ? 'danger' : ($task->priority === 'High' ? 'warning' : 'secondary') }} bg-opacity-10 text-{{ $task->priority === 'Critical' ? 'danger' : ($task->priority === 'High' ? 'warning' : 'secondary') }}">
                                                    {{ $task->priority }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                                <span><i class="fa fa-user me-1"></i>{{ $task->assignee?->name }}</span>
                                                <div class="d-flex gap-2">
                                                    <span><i class="fa fa-comments-o me-1"></i>{{ $task->active_notes_count }}</span>
                                                    @if ($task->unseen_count)
                                                        <span class="badge bg-success">{{ $task->unseen_count }} new</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-3 small">No tasks</div>
                                @endforelse
                            </div>
                            @can('task-management.create')
                                <div class="p-2 border-top">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100" wire:click="openCreateModalWithStatus('{{ $statusKey }}')">
                                        <i class="fa fa-plus me-1"></i> Add a card
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Table View --}}
    @if ($viewMode === 'table' && $tableData)
        <div class="p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom">
                    <thead class="bg-light text-nowrap">
                        <tr>
                            <th class="ps-3" style="width: 50px;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                                </div>
                            </th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" /></th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Title" /></th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="type" label="Type" /></th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="project" label="Project" /></th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="priority" label="Priority" /></th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /></th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="end_date" label="Due Date" /></th>
                            <th class="text-center">Notes</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tableData as $item)
                            <tr class="{{ $item->unseen_count ? 'table-success' : '' }}">
                                <td class="ps-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </div>
                                </td>
                                <td><span class="text-muted">#{{ $item->id }}</span></td>
                                <td>
                                    <button type="button" class="btn btn-link p-0 text-start text-decoration-none fw-semibold"
                                        wire:click="openViewModal({{ $item->id }})">
                                        {{ \Illuminate\Support\Str::limit($item->name, 40) }}
                                    </button>
                                </td>
                                <td>
                                    @if ($item->type)
                                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $item->type }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->project)
                                        <span class="badge bg-info bg-opacity-10 text-info">{{ $item->project }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $item->priority === 'Critical' ? 'danger' : ($item->priority === 'High' ? 'warning' : 'secondary') }} bg-opacity-10 text-{{ $item->priority === 'Critical' ? 'danger' : ($item->priority === 'High' ? 'warning' : 'secondary') }}">
                                        {{ $item->priority }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $item->status === 'Completed' ? 'success' : ($item->status === 'In Review' ? 'info' : ($item->status === 'In Progress' ? 'warning' : 'secondary')) }} bg-opacity-10 text-{{ $item->status === 'Completed' ? 'success' : ($item->status === 'In Review' ? 'info' : ($item->status === 'In Progress' ? 'warning' : 'secondary')) }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td>{{ $item->end_date ? systemDate($item->end_date) : '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark">{{ $item->active_notes_count }}</span>
                                    @if ($item->unseen_count)
                                        <span class="badge bg-success">{{ $item->unseen_count }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-info" wire:click="$dispatch('task-notes-open', { taskId: {{ $item->id }} })" title="Notes">
                                            <i class="fa fa-comments-o"></i>
                                        </button>
                                        @can('task-management.edit')
                                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openEditModal({{ $item->id }})" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                        @endcan
                                        @can('task-management.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="deleteSingle({{ $item->id }})" wire:confirm="Delete this task?" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No tasks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tableData)
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                        {{ $tableData->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
