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
    @livewire('rent-out.tabs.service-modal')
    @livewire('rent-out.tabs.utility-term-modal')
    <x-rent-out.document-modal />

    {{-- Scripts --}}
    @include('livewire.rent-out.partials.payment-term-scripts')
    @push('scripts')
        <x-select.documentTypeSelect />
        <x-select.paymentMethodSelect />
    @endpush
</x-app-layout>
