<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.view', ['id' => $id, 'agreementType' => $config->typeKey])
        </div>
    </div>

    {{-- Modals --}}
    <x-rent-out.single-payment-term-modal />
    <x-rent-out.multiple-payment-term-modal />
    <x-rent-out.security-modal />
    <x-rent-out.single-cheque-modal />
    <x-rent-out.multiple-cheque-modal />
    <x-rent-out.extend-modal />
    <x-rent-out.utility-term-modal />
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
