<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Appointment</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Appointment</h1>
            <p class="lead">
                A table is an arrangement of Appointment
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('appointment.table')
            </div>
        </div>
    </div>

    <x-appointment.booking-modal />
    <x-account.customer-modal />

    @push('scripts')
        <x-select.branchSelect />
        <x-select.userSelect />
        <x-select.employeeSelect />
        <x-select.customerSelect />
        <x-select.productSelect />
    @endpush
</x-app-layout>
