@php
    use App\Models\UserAttendance;
@endphp
<div class="content__boxed h-100">
    <div class="content__wrap">

        <div class="card rounded-0 rounded-bottom border-0 shadow-sm">
            <!-- Filters -->
            <div class="card-body border-bottom bg-light">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="search-box">
                            <label for="query">Search</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-white">
                                    <i class="demo-pli-search-website opacity-50"></i>
                                </span>
                                <input type="text" wire:model.live="filter.search" class="form-control border-0 bg-white" placeholder="Search employees...">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="month">Month</label>
                        <input type="month" wire:model.live="filter.month" class="form-control form-sm-control bg-white border-0" aria-label="Select month">
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label for="employee_id">Employee</label>
                        {{ html()->select('employee_id', [session('employee_id') => session('employee_name')])->value()->class('select-employee_id-list')->id('employee_id')->placeholder('Employee') }}
                    </div>

                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        @can('employee attendance.create')
                            <br>
                            <button class="btn btn-primary" id="PageAdd">
                                <i class="demo-psi-add fs-5 me-2"></i>
                                Record Attendance
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body pb-0">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="card bg-white h-100 border-primary border-opacity-25">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 p-3 bg-primary bg-opacity-10 rounded">
                                        <i class="demo-psi-calendar-4 fs-3 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-1">{{ $employees->count() }}</h5>
                                        <p class="mb-0 small text-muted">Total Employees</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-white h-100 border-success border-opacity-25">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 p-3 bg-success bg-opacity-10 rounded">
                                        <i class="demo-psi-like fs-3 text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-1">
                                            {{ $presentCount }}
                                        </h5>
                                        <p class="mb-0 small text-muted">Present Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-white h-100 border-danger border-opacity-25">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 p-3 bg-danger bg-opacity-10 rounded">
                                        <i class="demo-psi-close fs-3 text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-1">{{ $employees->count() - $presentCount }}</h5>
                                        <p class="mb-0 small text-muted">Absent Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card-body">
                <div class="table-responsive attendance-wrapper">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-dark fw-bold px-4 py-3 employee-column" scope="col">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                                </th>
                                @for ($i = 1; $i <= $daysInMonth; $i++)
                                    <th class="border-0 text-dark fw-bold text-center py-3 date-column" scope="col">{{ $i }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $employee)
                                <tr>
                                    <td class=" fw-medium employee-column">{{ $employee->name }}</td>
                                    @for ($i = 1; $i <= $daysInMonth; $i++)
                                        <td class="text-center date-column">
                                            @php
                                                $date = $currentMonth
                                                    ->copy()
                                                    ->addDays($i - 1)
                                                    ->format('Y-m-d');
                                                $attendance = $employee->attendances->where('date', $date)->first();
                                            @endphp

                                            @if ($attendance)
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 p-1">
                                                    <i class="fa fa-check text-success"></i>
                                                </div>
                                            @else
                                                @php
                                                    $attendanceMarked = UserAttendance::where('date', $date)->exists();
                                                @endphp
                                                @if ($attendanceMarked)
                                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 p-1">
                                                        <i class="fa fa-times text-danger"></i>
                                                    </div>
                                                @else
                                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 p-1">
                                                        <i class="fa fa-minus text-secondary"></i>
                                                    </div>
                                                @endif
                                            @endif

                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $data->links() }}
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* Smooth transitions */
            .btn,
            .form-control,
            .form-select {
                transition: all .2s ease-in-out;
            }

            /* Input styling */
            .search-box .input-group {
                box-shadow: 0 2px 4px rgba(0, 0, 0, .04);
            }

            .form-control,
            .form-select {
                padding: .75rem 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .04);
            }

            .form-control:focus,
            .form-select:focus {
                border-color: #5156be;
                box-shadow: 0 0 0 0.2rem rgba(81, 86, 190, .15);
            }

            /* Table styling */
            .attendance-wrapper {
                border: 1px solid #e9ecef;
                border-radius: 0.375rem;
                position: relative;
            }

            .table {
                margin-bottom: 0;
            }

            .table td,
            .table th {
                border-color: #e9ecef;
            }

            /* Fixed columns and headers */
            .employee-column {
                position: sticky !important;
                left: 0;
                background-color: #fff !important;
                z-index: 2;
                min-width: 200px;
            }

            thead .employee-column {
                background-color: #f8f9fa !important;
                z-index: 3;
            }

            thead tr th {
                position: sticky;
                top: 0;
                background-color: #f8f9fa !important;
                z-index: 1;
            }

            thead .employee-column {
                z-index: 3;
            }

            .date-column {
                min-width: 45px;
                width: 45px;
            }

            tbody tr:hover .employee-column {
                background-color: #f8f9fa !important;
            }

            /* Status indicators */
            [data-bs-toggle="tooltip"] {
                width: 32px;
                height: 32px;
                cursor: pointer;
                transition: transform .15s ease;
            }

            [data-bs-toggle="tooltip"]:hover {
                transform: scale(1.1);
            }

            /* Scrollbar styling */
            .attendance-wrapper::-webkit-scrollbar {
                height: 6px;
            }

            .attendance-wrapper::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }

            .attendance-wrapper::-webkit-scrollbar-thumb {
                background: #d1d5db;
                border-radius: 3px;
            }

            .attendance-wrapper::-webkit-scrollbar-thumb:hover {
                background: #9ca3af;
            }

            /* Stats card hover effect */
            .card {
                transition: transform .2s ease, box-shadow .2s ease;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08);
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .employee-column {
                    min-width: 150px;
                }

                [data-bs-toggle="tooltip"] {
                    width: 28px;
                    height: 28px;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#PageAdd').click(function() {
                    Livewire.dispatch("Create-Attendance-Page-Component");
                });
                window.addEventListener('RefreshAttendanceTable', event => {
                    Livewire.dispatch("Attendance-Refresh-Component");
                });
                $('#employee_id').on('change', function(e) {
                    @this.set('filter.employee_id', $(this).val());
                });
            });
        </script>
    @endpush
</div>
