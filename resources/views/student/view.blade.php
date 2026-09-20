<x-app-layout>
    <div class="container-fluid py-3">
        @livewire('student.view', ['account_id' => $id])
    </div>

    {{-- Outside the view component on purpose: a modal rendered inside a tab is
         laid out against that panel and gets clipped by it. --}}
    @can('student topup.view')
        @livewire('student.topup-modal', ['account_id' => $id])
    @endcan
</x-app-layout>
