<x-app-layout>
    @push('styles')
    @endpush
    @livewire('user.table')
    @push('scripts')
    @endpush
    <x-user.user-modal />
</x-app-layout>
