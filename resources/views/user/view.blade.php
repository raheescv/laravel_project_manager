<x-app-layout>
    @push('styles')
    @endpush
    @livewire('user.view', ['table_id' => $id])
    <x-user.user-modal :id="$id" />
    <x-user.employee-modal :id="$id" />
    @push('scripts')
        <x-select.branchSelect :id="$id" />
    @endpush
</x-app-layout>
