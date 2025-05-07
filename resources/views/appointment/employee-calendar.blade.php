<x-app-layout>
    <div class="content__header content__boxed overlapping" id="AppointmentHeaderArea">
        <div class="content__wrap">
            <!-- Enhanced breadcrumb -->
            <nav aria-label="breadcrumb" class="animate__animated animate__fadeIn">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-primary hover:text-primary-dark"><i class="demo-pli-home fs-5 me-2"></i>Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Appointment</li>
                </ol>
            </nav>

            <div class="d-md-flex align-items-center justify-content-between gap-4">
                <div class="animate__animated animate__fadeInLeft">
                    <div class="d-flex align-items-center gap-2">
                        <i class="demo-pli-calendar-4 fs-1 text-primary"></i>
                        <div>
                            <h1 class="page-title mb-0 mt-2">Appointment Calendar</h1>
                            <p class="lead mb-0 text-muted">
                                Schedule and manage appointments with ease
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 align-items-center flex-wrap animate__animated animate__fadeInRight">
                    @can('appointment.create')
                        <button class="btn btn-primary hstack gap-2" data-bs-toggle="modal" data-bs-target="#AppointmentBookingModal">
                            <i class="demo-psi-add fs-5"></i>
                            <span class="vr"></span>
                            <span>New Appointment</span>
                        </button>
                    @endcan
                    @can('appointment.view')
                        <a class="btn btn-outline-primary hstack gap-2" href="{{ route('appointment::list') }}">
                            <i class="demo-pli-list-view fs-5"></i>
                            <span class="vr"></span>
                            <span>View List</span>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card shadow-sm">
                <div class="bg-white rounded-3">
                    @livewire('appointment.employee-calendar')
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendors/fullcalendar/fullcalendar.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendors/fullcalendar/fullcalendar-scheduler.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-color/bootstrap-colorselector.css') }}">
        <style>
            .content__boxed {
                padding-top: 0;
            }

            .card {
                border: none;
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
                transition: box-shadow 0.2s ease;
            }

            .card:hover {
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            }

            .breadcrumb {
                margin-bottom: 0.5rem;
            }

            .breadcrumb-item+.breadcrumb-item::before {
                content: "›";
            }

            .page-title {
                color: #1e293b;
                font-weight: 600;
            }

            .lead {
                color: #64748b;
            }

            .fc-event-time,
            .fc-event-title {
                color: #ffffff !important;
                padding: 0 1px;
            }

            #AppointmentHeaderArea {
                background: linear-gradient(to right, #f8fafc, #f1f5f9);
                border-bottom: 1px solid #e2e8f0;
            }

            #AppointmentHeaderArea .page-title {
                color: #1e293b;
                font-weight: 600;
                font-size: 1.75rem;
                line-height: 1.2;
            }

            #AppointmentHeaderArea .lead {
                color: #64748b;
                font-size: 1rem;
            }

            #AppointmentHeaderArea .breadcrumb {
                margin-bottom: 1rem;
            }

            #AppointmentHeaderArea .breadcrumb-item+.breadcrumb-item::before {
                content: "›";
                font-size: 1.2em;
                line-height: 1;
                vertical-align: middle;
            }

            #AppointmentHeaderArea .btn {
                transition: all 0.2s ease;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            }

            #AppointmentHeaderArea .btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            /* Animations */
            .animate__animated {
                animation-duration: 0.6s;
            }
        </style>
    @endpush

    <x-appointment.booking-modal />
    <x-account.customer-modal />

    @push('scripts')
        <x-select.employeeSelect />
        <x-select.customerSelect />
        <x-select.productSelect />
        <script src="{{ asset('assets/vendors/fullcalendar/index.global.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/fullcalendar/bootstrap-plugin.global.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/fullcalendar/fullcalendar-scheduler.js') }}"></script>
        <script src="{{ asset('assets/vendors/bootstrap-color/bootstrap-colorselector.js') }}"></script>
        <script>
            $('#root').attr('class', 'root mn--push');
        </script>
    @endpush
</x-app-layout>
