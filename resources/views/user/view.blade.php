<x-app-layout>
    @push('styles')
    @endpush
    @livewire('user.view', ['table_id' => $id])
    <x-user.user-modal :id="$id" />
    <x-user.employee-modal :id="$id" />
    <x-settings.designation.designation-modal />
    @push('scripts')
        <x-select.branchSelect :id="$id" />
        <x-select.designationSelect />
    @endpush
</x-app-layout>
