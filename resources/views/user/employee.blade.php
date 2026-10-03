<x-app-layout>
    @livewire('user.employee.table')
    <x-user.employee-modal />
    <x-settings.designation.designation-modal />
    @push('scripts')
        <x-select.designationSelect />
        <x-select.branchSelect />
    @endpush
</x-app-layout>
