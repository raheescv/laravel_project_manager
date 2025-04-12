<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale_return::index') }}">Sale Return</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payments</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sale Return Payments</h1>
            <p class="lead">
                A table is list of Sale Return Payments
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('sale-return.payments')
            </div>
        </div>
    </div>
    <x-sale-return.customer-payment-modal />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.branchSelect')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
