<x-app-layout>
    @push('styles')
    @endpush
    @livewire('tenant.table')
    @push('scripts')
    @endpush
    <x-tenant.tenant-modal />
</x-app-layout>


