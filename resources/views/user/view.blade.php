<x-app-layout>
    @push('styles')
    @endpush
    @livewire('user.view', ['table_id' => $id])
    @push('scripts')
    @endpush
    <x-user.user-modal :id="$id" />
    <x-user.employee-modal :id="$id" />
</x-app-layout>
