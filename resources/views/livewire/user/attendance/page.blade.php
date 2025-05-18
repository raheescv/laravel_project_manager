<div class="container-fluid p-0">
    <div class="modal-header bg-primary text-white">
        <h1 class="modal-title fs-5 text-white"><i class="pli-calendar-4 me-2"></i>Daily Attendance</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Employees</h6>
                                    <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                                </div>
                                <div class="p-2 rounded-circle bg-primary bg-opacity-10">
                                    <i class="pli-business-mens fs-4 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Present Today</h6>
                                    <h3 class="mb-0 fw-bold">{{ $stats['present'] }}</h3>
                                </div>
                                <div class="p-2 rounded-circle bg-success bg-opacity-10">
                                    <i class="pli-yes fs-4 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Absent Today</h6>
                                    <h3 class="mb-0 fw-bold">{{ $stats['absent'] }}</h3>
                                </div>
                                <div class="p-2 rounded-circle bg-danger bg-opacity-10">
                                    <i class="pli-close fs-4 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Attendance Rate</h6>
                                    <h3 class="mb-0 fw-bold">{{ $stats['percentage'] }}%</h3>
                                </div>
                                <div class="p-2 rounded-circle bg-info bg-opacity-10">
                                    <i class="pli-bar-chart-4 fs-4 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Selection -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-bold">
                            <i class="pli-calendar me-1"></i>Select Date
                        </label>
                        <div class="input-group">
                            {{ html()->date('date')->class('form-control')->required(true)->attribute('wire:model.live', 'date') }}
                            <button type="button" wire:click="getList" class="btn btn-primary">
                                <i class="pli-repeat-2 me-1"></i>Fetch
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee List -->
            <div class="card shadow-sm">
                <div class="card-header bg-light py-2">
                    <div class="form-check d-flex align-items-center">
                        <input type="checkbox" class="form-check-input me-2" wire:model.live="selectAll" id="selectAll">
                        <label class="form-check-label fw-semibold" for="selectAll">Select All Employees</label>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        @foreach ($employees->chunk(ceil($employees->count() / 2)) as $chunk)
                            <div class="col-md-6">
                                @foreach ($chunk as $employee)
                                    <div class="list-group-item list-group-item-action border-0 border-bottom p-3" role="button">
                                        <div class="d-flex justify-content-between align-items-center" wire:click="toggleAttendance({{ $employee->id }})">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <span class="initials">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $employee->name }}</h6>
                                                    <small class="text-muted">{{ $employee->code }}</small>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" wire:model.live="attendance.{{ $employee->id }}" onclick="event.stopPropagation()">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer border-top">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                <i class="pli-cross me-1"></i>Close
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="pli-disk me-1"></i>Save Changes
            </button>
        </div>
    </form>

    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-circle .initials {
            font-size: 1.2rem;
            font-weight: 500;
            color: #495057;
        }

        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</div>
