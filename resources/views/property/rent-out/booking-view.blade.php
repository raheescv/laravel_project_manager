<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.booking-view', ['id' => $id, 'agreementType' => $config->typeKey])
        </div>
    </div>

    {{-- Livewire Modals --}}
    @livewire('rent-out.tabs.single-payment-term-modal')
    @livewire('rent-out.tabs.multiple-payment-term-modal')
    @livewire('rent-out.tabs.security-modal')
    @livewire('rent-out.tabs.single-cheque-modal')
    @livewire('rent-out.tabs.multiple-cheque-modal')
    @livewire('rent-out.tabs.extend-modal')
    @livewire('rent-out.tabs.utility-term-modal')
    <x-rent-out.document-modal />
    <x-rent-out.service-modal />
    <x-rent-out.service-charge-modal />
    <x-rent-out.service-payment-modal />
    <x-rent-out.payout-modal />

    {{-- Scripts --}}
    @include('livewire.rent-out.partials.payment-term-scripts')
    @push('scripts')
        <x-select.documentTypeSelect />
        <x-select.paymentMethodSelect />
        <x-select.accountSelect />
    @endpush
</x-app-layout>
