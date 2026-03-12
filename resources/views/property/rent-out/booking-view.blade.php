<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route($config->bookingRoute) }}">{{ $config->bookingLabel }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View {{ $config->bookingLabel }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">View {{ $config->bookingLabel }}</h1>
            <p class="lead">
                {{ $config->bookingLabel }} details and information
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.view', ['id' => $id, 'agreementType' => $config->typeKey])
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
