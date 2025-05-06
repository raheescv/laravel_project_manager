<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-secondary">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Appointment</li>
                </ol>
            </nav>
            <div class="d-md-flex align-items-center gap-4">
                <div>
                    <h1 class="page-title mb-0 mt-2">Appointment Calendar</h1>
                    <p class="lead mb-0">
                        Schedule and manage appointments with ease
                    </p>
                </div>
                <div class="ms-md-auto d-flex gap-2 align-items-center flex-wrap">
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#AppointmentBookingModal">
                        <i class="demo-psi-add fs-5"></i>
                        <span>New Appointment</span>
                    </button>
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
