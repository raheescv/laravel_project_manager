<x-app-layout>
    <div class="container-fluid py-3">
        @livewire('student.view', ['account_id' => $id])
    </div>

    {{-- Outside the view component on purpose: a modal rendered inside a tab is
         laid out against that panel and gets clipped by it. --}}
    @canany(['student topup.create', 'student topup.refund'])
        @livewire('student.topup-modal', ['account_id' => $id])
    @endcanany
</x-app-layout>
