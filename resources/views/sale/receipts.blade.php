<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::index') }}">Sale</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Receipts</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sale Receipts</h1>
            <p class="lead">
                A table is list of Sale Receipts
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('sale.receipts')
            </div>
        </div>
    </div>
    <x-sale.customer-receipt-modal />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.branchSelect')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
