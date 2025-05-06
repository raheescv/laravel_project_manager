<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Appointment</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Appointments</h1>
            <p class="lead">
                A full-sized drag & drop event calendar.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card">
                @livewire('appointment.employee-calendar')
            </div>
        </div>
    </div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendors/fullcalendar/fullcalendar.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendors/fullcalendar/fullcalendar-scheduler.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-color/bootstrap-colorselector.css') }}">
        <style media="screen">
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
