<x-app-layout>
    <div class="container-fluid py-3">
        @livewire('property-appointment.table')
    </div>

    @push('scripts')
        <x-select.employeeSelect />
    @endpush
</x-app-layout>
