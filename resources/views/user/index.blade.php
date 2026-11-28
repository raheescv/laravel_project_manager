<x-app-layout>
    @push('styles')
    @endpush
    @livewire('user.table')
    <x-user.user-modal />
    <x-settings.designation.designation-modal />
    @push('scripts')
        <x-select.designationSelect />
    @endpush
</x-app-layout>
